#!/usr/bin/python3
                                                        # pip install tensorflow
from keras.preprocessing.text import Tokenizer          # pip install keras
from keras_preprocessing.sequence import pad_sequences  # pip install keras_preprocessing
from keras.models import Model, load_model
import pandas as pd                                     # pip install pandas
from encodings import normalize_encoding                # pip install encodings
from tqdm import tqdm                                   # pip install tqdm
import re                                               # pip install re
import json                                             # pip install json
import spacy                                            # pip install spacy
                                                        # python -m spacy download en
import pickle

import random
import time
import sys

def classifyMessage(message):


    # model_with_best_f1:
    # {   'f1_score': 0.8333220453363839,
    #     'f1_score_per_class': {   0: 0.8648648648648648,
    #                               1: 0.8391608391608391,
    #                               2: 0.7826086956521738,
    #                               3: 0.6382978723404255,
    #                               4: 0.8750000000000001,
    #                               5: 1.0},
    #     'model_name': 'models_model_3_2000epoch.h5',
    #     'thresholds': {   0: 0.8497534359086438,
    #                       1: 0.008902150854450374,
    #                       2: 0.2310129700083158,
    #                       3: 0.005462277217684337,
    #                       4: 0.02782559402207126,
    #                       5: 0.2310129700083158}}

    model = load_model("models/models_model_3_2000epoch.h5")
    thresholds = [0.8497534359086438, 0.008902150854450374, 0.2310129700083158, 0.005462277217684337, 0.02782559402207126, 0.2310129700083158]

    # Load prefitted tokenizer
    with open("./tokenizer", 'rb') as handle:
        tokenizer = pickle.load(handle)

    preprocessed_message = preprocess_message(message)
    tokenized_message = tokenizer.texts_to_sequences([preprocessed_message])
    padded_message = pad_sequences(tokenized_message, padding='post', maxlen=200)
    predictions = model.predict(padded_message)[0]

    binary_predictions = predictions.copy()
    binary_predictions[predictions>=thresholds]=1
    binary_predictions[predictions<thresholds]=0

    #print("Predictions as floats: \n", predictions)
    #print("Binary predictions based on thresholds: \n", binary_predictions)

    return binary_predictions

def preprocess_message(sen):
    # Unrealized preprocessing:
    #   - Retrieve names of people from column of .xslx and replace the names in the messages with tokens
    #   - replace times (f.e. 6pm) with special token
    #   - Make sure @ tags are tokenized
    #   - Remove short words like "the" etc -> stopwords

    # Convert URLs to single token "url
    sen = replace_urls(sen)

    # Normalize whitespaces
    sen = normalize_whitespace(sen)

    # Normalize contractions (f.e. cant -> cannot)
    sen = normalize_contractions(sen)

    # Convert Text to lower case
    sen = sen.lower()

    # Convert emojis to universal string: "emoji"
    sen = convert_emojis(sen)

    # Convert punctuations with writte equivalent
    sen = sen.replace(".", " dot")
    sen = sen.replace(":", " doubledot")
    sen = sen.replace("!", " exclamationmark")
    sen = sen.replace("?", " questionmark")

    # Remove punctuations and numbers
    sen = re.sub('[^a-zA-Z]', ' ', sen)

    # Lemmatize the sentences
    sen = lemmatize(sen)

    # Single character removal
    sen = re.sub(r"\s+[a-zA-Z]\s+", ' ', sen)

    # Removing multiple spaces
    sen = re.sub(r'\s+', ' ', sen)

    return sen

def normalize_contractions(sen):
    contractions = json.loads(open('./contractions.json', 'r').read())

    new_token_list = []
    token_list = sen.split()
    for word_pos in range(len(token_list)):
        word = token_list[word_pos]
        first_upper = False
        if word[0].isupper():
            first_upper = True
        if word.lower() in contractions:
            replacement = contractions[word.lower()]
            if first_upper:
                replacement = replacement[0].upper()+replacement[1:]
            replacement_tokens = replacement.split()
            if len(replacement_tokens)>1:
                new_token_list.append(replacement_tokens[0])
                new_token_list.append(replacement_tokens[1])
            else:
                new_token_list.append(replacement_tokens[0])
        else:
            new_token_list.append(word)
    sen = " ".join(new_token_list).strip(" ")
    return sen

def normalize_whitespace(sen):
    corrected = str(sen)
    corrected = re.sub(r"//t",r"\t", corrected)
    corrected = re.sub(r"( )\1+",r"\1", corrected)
    corrected = re.sub(r"(\n)\1+",r"\1", corrected)
    corrected = re.sub(r"(\r)\1+",r"\1", corrected)
    corrected = re.sub(r"(\t)\1+",r"\1", corrected)
    return corrected.strip(" ")

def replace_urls(sen):
    url_regex = r'(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})'
    sen = re.sub(url_regex, "<URL>", sen)
    return sen

def convert_emojis(sen):
    emoj = re.compile("["
        u"\U0001F600-\U0001F64F"  # emoticons
        u"\U0001F300-\U0001F5FF"  # symbols & pictographs
        u"\U0001F680-\U0001F6FF"  # transport & map symbols
        u"\U0001F1E0-\U0001F1FF"  # flags (iOS)
        u"\U00002500-\U00002BEF"  # chinese char
        u"\U00002702-\U000027B0"
        u"\U00002702-\U000027B0"
        u"\U000024C2-\U0001F251"
        u"\U0001f926-\U0001f937"
        u"\U00010000-\U0010ffff"
        u"\u2640-\u2642"
        u"\u2600-\u2B55"
        u"\u200d"
        u"\u23cf"
        u"\u23e9"
        u"\u231a"
        u"\ufe0f"  # dingbats
        u"\u3030"
                      "]+", re.UNICODE)
    return re.sub(emoj, ' emoji', sen)

def lemmatize(sentence):
    sentence = sentence.strip()
    nlp = spacy.load('en_core_web_sm')
    sent = ""
    doc = nlp(sentence)
    for token in doc:
        if '@' in token.text:
            sent+=" @MENTION"
        elif '#' in token.text:
            sent+= " #HASHTAG"
        else:
            sent+=" "+token.lemma_
    return sent

#def connect_mqtt():
#    def on_connect(client, userdata, flags, rc):
#        if rc == 0:
#            print("Connected to MQTT Broker!")
#        else:
#            print("Failed to connect, return code %d\n", rc)
#    # Set Connecting Client ID
#    client = mqtt_client.Client(client_id)
#    client.on_connect = on_connect
#    client.connect(broker, port)
#    return client
#
#def publish(client):
#     msg_count = 0
#     while True:
#         time.sleep(1)
#         msg = f"messages: {msg_count}"
#         result = client.publish(topic, msg)
#         # result: [0, 1]
#         status = result[0]
#         if status == 0:
#             print(f"Send `{msg}` to topic `{topic}`")
#         else:
#             print(f"Failed to send message to topic {topic}")
#         msg_count += 1
#
#def subscribe(client: mqtt_client):
#    def on_message(client, userdata, msg):
#        print(f"Received `{msg.payload.decode()}` from `{msg.topic}` topic")
#        result = classifyMessage(msg.payload.msg)
#        client.publish(msg.topic, result)
#
#    client.subscribe(topic)
#    client.on_message = on_message
#### Usage ###
#
##result = classifyMessage("Hello World!")
#
#broker = 'mosquitto'
#port = 1883
#topic = "classify/#"
#client_id = f'python-mqtt-{random.randint(0, 1000)}'
#
#client = connect_mqtt()
#
#subscribe(client)

print(classifyMessage(sys.argv[1]))
