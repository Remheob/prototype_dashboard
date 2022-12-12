FROM debian:latest

RUN apt update
RUN apt upgrade -y
RUN apt install python3 python3-pip -y

COPY production_version /production_version
WORKDIR /production_version

RUN pip install tensorflow keras keras_preprocessing pandas tqdm spacy paho-mqtt
RUN python3 -m spacy download en

CMD [ "python3", "main.py"]
