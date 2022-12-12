<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\TeamsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpMqtt\Client\Facades\MQTT;

class Submit extends Controller
{
    //

    public function submitMessage(Request $request){
        $reqContent = json_decode($request->getContent(), true);
        if ($reqContent !== null){
            if (array_key_exists('net_values', $reqContent)){
                unset($reqContent['net_values']);
            }
            $results = DB::select('select id from teams_users where user_id = ?', [$reqContent['user_id']]);
            $userID = null;
            if (count($results) > 0){
                $userID = $results[0]->id;
            }else{
                DB::insert('INSERT INTO teams_users (user_id, name) VALUES (?, ?)', [$reqContent['user_id'], $reqContent['user_name']]);
                $results = DB::select('SELECT LAST_INSERT_ID() AS lastid;', []);
                $userID = $results[0]->lastid;
            }

            $return = DB::insert('
            INSERT INTO messages (
                                  created_at,
                                  user_id,
                                  message_id,
                                  message,
                                  conversation_id,
                                  channelId,
                                  date,
                                  time,
                                  conversationType
            ) VALUES (
                      current_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?)', [$userID, $reqContent['message_id'], $reqContent['message'], $reqContent['conversation_id'], $reqContent['channelId'], $reqContent['date'], $reqContent['time'], $reqContent['conversationType']]);
            if ($return){
                //MQTT::publish('classify/' . $reqContent['message_id'], $reqContent['message']);
                #$result = shell_exec("python3 ../classifier/production_version/main.py 2>/dev/null | tail -n 1");
                $command = escapeshellcmd('bash ../runmodel.sh "' . $reqContent['message'] . '"');
                $result = shell_exec($command);
                $result = str_replace('[', '', $result);
                $result = str_replace(']', '', $result);
                $result = str_replace('.', '', $result);
                $result = str_replace(' ', '', $result);
                DB::table('messages')
                    ->where('message_id', $reqContent['message_id'])
                    ->update(['net_values' => $result]);
                return response()->json([
                    'success' => 1,
                    'result' => $result,
                ]);
            }else{
                return response()->json([
                    'error' => "message could not be inserted into table"
                ]);
            }
        }else{
            return response()->json([
                'error' => "invalid jason " . json_last_error_msg()
            ]);

        }
        return response()->json([
            'error' => "bro, wie zum fick bin ich hier her gekommen???"
        ]);
    }
}
