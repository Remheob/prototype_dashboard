<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Admin extends Controller
{
    //
    public function index(){
        $result = DB::select("
            SELECT
                m.created_at,
                tu.name,
                message_id,
                message,
                net_values
            FROM messages m
                     inner join teams_users tu on m.user_id = tu.id
            WHERE WEEK(m.created_at) >= WEEK(current_timestamp()) - 2;");
        $result = DB::select("SELECT count(id) as anz, conversation_id FROM messages group by conversation_id");

        $users = DB::select("SELECT t.name, t.user_id as teams_user_id, m.user_id, Count(*) as anz FROM messages m INNER JOIN teams_users t on t.id = m.user_id group by m.user_id");

        $total = DB::select("SELECT count(id) as totalanz FROM messages");

        if (is_array($result) && is_array($users)){
            return view('admin-dashboard')  ->with('result', $result)
                                            ->with('users', $users)
                                            ->with('total', $total);
        }else {
            echo "kp, hier ist was falsch";
        }
    }

    public function byConversationID(String $convID){
        $result = DB::select("SELECT m.created_at, m.message, m.net_values, m.message_id, m.conversation_id, m.date, m.time, t.user_id, t.name FROM messages m INNER JOIN teams_users t on m.user_id = t.id WHERE conversation_id = ?", [$convID]);
        $message_categories_in_columns = DB::select("SELECT 
                                            SUM(SUBSTRING(net_values, 1, 1)) as `Discussion`, 
                                            SUM(SUBSTRING(net_values, 2, 1)) as `OnTopic`, 
                                            SUM(SUBSTRING(net_values, 3, 1)) as `OrganizationalArrangements`, 
                                            SUM(SUBSTRING(net_values, 4, 1)) as `OffTopicDiscussion`, 
                                            SUM(SUBSTRING(net_values, 5, 1)) as `NeedForIntervention`, 
                                            SUM(SUBSTRING(net_values, 6, 1)) as `RequestForFeedback` 
                                        FROM messages
                                        WHERE conversation_id = ?", [$convID]);
        $message_categories_in_array = array(
            "Discussion" => $message_categories_in_columns[0] -> Discussion,
            "On-Topic" => $message_categories_in_columns[0] -> OnTopic,
            "Organizational arrangements" => $message_categories_in_columns[0] -> OrganizationalArrangements,
            "Off-Topic discussion" => $message_categories_in_columns[0] -> OffTopicDiscussion,
            "Need for Intervention" => $message_categories_in_columns[0] -> NeedForIntervention,
            "Request for feedback" => $message_categories_in_columns[0] -> RequestForFeedback
        );
        if (is_array($result) && is_array($message_categories_in_array)){
            return view('conversation-dashboard')   ->with('result', $result)
                                                    ->with('convID', $convID)
                                                    ->with('absCount_message_categories', $message_categories_in_array);
        }else {
            echo "kp, hier ist was falsch";
        }

        return view('conversation-dashboard');
    }

    public function byUserID(String $userID){
        $result = DB::select("SELECT * FROM messages WHERE user_id = (SELECT id FROM teams_users WHERE user_id = ?)", [$userID]);
        $message_categories_in_columns = DB::select("SELECT 
                                            SUM(SUBSTRING(net_values, 1, 1)) as `Discussion`, 
                                            SUM(SUBSTRING(net_values, 2, 1)) as `OnTopic`, 
                                            SUM(SUBSTRING(net_values, 3, 1)) as `OrganizationalArrangements`, 
                                            SUM(SUBSTRING(net_values, 4, 1)) as `OffTopicDiscussion`, 
                                            SUM(SUBSTRING(net_values, 5, 1)) as `NeedForIntervention`, 
                                            SUM(SUBSTRING(net_values, 6, 1)) as `RequestForFeedback` 
                                        FROM messages
                                        WHERE user_id = (SELECT id FROM teams_users WHERE user_id = ?)", [$userID]);
        $message_categories_in_array = array(
            "Discussion" => $message_categories_in_columns[0] -> Discussion,
            "On-Topic" => $message_categories_in_columns[0] -> OnTopic,
            "Organizational arrangements" => $message_categories_in_columns[0] -> OrganizationalArrangements,
            "Off-Topic discussion" => $message_categories_in_columns[0] -> OffTopicDiscussion,
            "Need for Intervention" => $message_categories_in_columns[0] -> NeedForIntervention,
            "Request for feedback" => $message_categories_in_columns[0] -> RequestForFeedback
        );

        $userName = DB::select("SELECT name FROM teams_users WHERE id = (SELECT id FROM teams_users WHERE user_id = ?)", [$userID]);
        if (is_array($result)){
            return view('user-dashboard')->with('result', $result)
                                         ->with('absCount_message_categories', $message_categories_in_array)
                                         ->with('userName', $userName[0] -> name);
        }else {
            echo "kp, hier ist was falsch";
        }

        return view('user-dashboard');
    }

    public function byUserIDAndConversationID(String $userID, String $conversationID){
        $result = DB::select("SELECT * FROM messages WHERE user_id = (SELECT id FROM teams_users WHERE user_id = ?) AND conversation_id = ?", [$userID, $conversationID]);
        if (is_array($result)){
            return view('user-dashboard')->with('result', $result);
        }else {
            echo "kp, hier ist was falsch";
        }
        //todo
        return view('user-dashboard');
    }
}
