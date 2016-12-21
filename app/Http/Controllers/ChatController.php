<?php
/**
 * Created by PhpStorm.
 * User: Shakeel Latif
 * Date: 8/19/15
 * Time: 12:18 PM
 */

namespace App\Http\Controllers;
use \Validator;
use \Response;
use \App\User;
use \App\Chat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class ChatController extends Controller
{
    public function __construct()
    {
    }

    public function sendMessage()
    {
        
        $alldata = $_POST;
       // print_r($alldata);exit;
        
        $validator = Validator::make($alldata, Chat::$sendMessageRules);
        if ( $validator->fails() )
        {
            return Response::json(['status' => 'error', 'errorMessage' => 'The following validations are failed!',  'Data' => $validator->getMessageBag()->toArray() ]);
        }   
        else
        {
            $returnedobj = new \stdClass();
            $sender_id = $alldata['user_id'];
            $receiver_id = $alldata['receiver_id'];
            $chatMessage = new Chat;
            $chatMessage->sender_id = $sender_id;
            $chatMessage->receiver_id = $receiver_id;
            $chatMessage->chat_message = $alldata['chat_message'];
            $chatMessage->dr_sender_id = 0;
            $chatMessage->dr_receiver_id = 0;
            $returnedobj->sender_id = $sender_id;
            $returnedobj->receiver_id = $receiver_id;
            $returnedobj->chat_message = $alldata['chat_message'];       
            if ($chatMessage->save())
            {
                $user_data = User::where('id', '=', $sender_id)->select('f_name','l_name', 'profile_pic','timezone')->first();

                $returnedobj->f_name = $user_data['f_name'];
                $returnedobj->l_name = $user_data['l_name'];
                $returnedobj->profile_pic = profilePicPath($user_data['profile_pic']);
                $returnedobj->message_id = $chatMessage->id;
                $returnedobj->user_type = 1;
                $returnedobj->type = "message";
                
                //Send Notification to Mobile App
                $user = User::find($receiver_id);
                if($user){
                    $message= "Someone Sent you a message";
                    $date = new \DateTime($chatMessage->created_at);
                    $date->setTimezone(new \DateTimeZone(timezoneobj($user->timezone)));
                    $returnedobj->created_at = $date->format('F-d-Y h:i A');
                    $payload_data=array('user_details' => $returnedobj);
                    pushNotification($user->plateform,$user->device_id,$message,$payload_data,$user->id);
                }
                $date = new \DateTime($chatMessage->created_at);
                $date->setTimezone(new \DateTimeZone(timezoneobj($user_data['timezone'])));
                $returnedobj->created_at = $date->format('F-d-Y h:i A');
                return Response::json(['status' => 'success', 'SuccessMessage' => env('SUCCESS_106'), 'Data' => $returnedobj ]);
            }
            else
            {
                return Response::json(['status' => 'error', 'errorMessage' => 'Some Problem occurred, please try again later!']);
            }
        }
    }

    public function viewMessages()
    {
        
        $page_id = 1;
        $take = 10;
        $alldata = json_decode(file_get_contents('php://input'), true);
        if (isset($alldata['page_id']) && $alldata['page_id'] > 0)
        {
            $page_id = $alldata['page_id']; // 0
        }
        $skip = ($page_id-1)*$take;
        $take = $take;
        $validator = Validator::make($alldata, Chat::$viewMessageRules);
        if ( $validator->fails() )
        {
            return Response::json(['status' => 'error', 'errorMessage' => 'The following validations are failed!',  'Data' => $validator->getMessageBag()->toArray() ]);
        }
        else
        {
            $user_id = $alldata['user_id'];
            $fan_id = $alldata['fan_id'];
            $user = User::find($fan_id);
            $more_messages = 0;
            $status = 0;
            if($user->device_id != ""){
                $status = 1;
            }
            if ( intval($fan_id) > 0 )
            {   
                $no_of_messages_obj = DB::select("SELECT COUNT(c.id) as no_of_messages from users as u INNER JOIN chats as c ON (u.id=c.sender_id ) where (c.sender_id=$user_id and c.receiver_id=$fan_id and dr_sender_id = 0) or (c.sender_id=$fan_id and c.receiver_id=$user_id and dr_receiver_id = 0) group by c.created_at");
                
                $total_pages = (count($no_of_messages_obj))/$take;
                if($total_pages>$page_id){
                    $more_messages = 1;
                }
                    
                $chat_messages = DB::select("SELECT u.f_name, u.l_name, c.id as message_id, u.profile_pic, c.sender_id, c.chat_message, c.created_at from users as u INNER JOIN chats as c ON (u.id=c.sender_id ) where (c.sender_id=$user_id and c.receiver_id=$fan_id and dr_sender_id = 0) or (c.sender_id=$fan_id and c.receiver_id=$user_id and dr_receiver_id = 0) group by c.created_at order by c.created_at DESC LIMIT $skip,$take");
                $chat_messages = array_reverse($chat_messages);
                foreach ($chat_messages as $ch)
                {
                    $ch->profile_pic=  profilePicPath($ch->profile_pic);
                    $date = new \DateTime($ch->created_at);
                    $date->setTimezone(new \DateTimeZone(timezoneobj($user->timezone)));
                    $ch->created_at = $date->format('F-d-Y h:i A');
                    if($ch->sender_id == $user_id)
                        $ch->user_type = 1;
                    else
                        $ch->user_type = 0;
                }
            }
            else
            {
                $chat_messages = [];
            }
            //array_slice($chat_messages, $skip, $take)
            return Response::json(['status' => 'success', 'SuccessMessage' => 'Success!', 'Data' => ['messages' => $chat_messages ],'online_status' => $status,'current_page_id' => $page_id,'more_messages' => $more_messages]);
        }
    }

    public function viewBuddyList()
    {
        $skip = 0; $take = 10;
        $alldata = json_decode(file_get_contents('php://input'), true);
        if (isset($alldata['from']))
        {
            $skip = $alldata['from'];
        }

        if ( isset($alldata['to']) )
        {
            $take = $alldata['to'] - $skip;
        }

        $user_id = $alldata['user_id'];

        $chat_buddies = DB::select("select * from (SELECT u.id as user_id, CONCAT(u.f_name) as f_name, u.profile_pic, c.id as message_id, c.viewed as is_read, c.sender_id, c.receiver_id, c.chat_message, c.created_at from users as u INNER JOIN chats as c ON (u.id=c.sender_id OR u.id=c.receiver_id) where (c.sender_id=$user_id or c.receiver_id=$user_id) and u.id<>$user_id order by c.created_at desc) as dummy group by user_id order by created_at desc");//" LIMIT $skip OFFSET $take");

        foreach ( $chat_buddies as $ch )
        {
            $ch->created_at=  timeago(strtotime($ch->created_at));
        }

        $m_seen_count = Chat::where('receiver_id', $user_id)->where('seen_count', 0)->groupBy('receiver_id')->get();

        return Response::json(['status' => 'success', 'SuccessMessage' => 'Success!', 'Data' => [ 'buddy_list' => array_slice($chat_buddies, $skip, $take), 'm_seen_count' => count($m_seen_count) ] ]);
    }

    public function deleteMessages()
    {
        $alldata = json_decode(file_get_contents('php://input'), true);
        $validator = Validator::make($alldata, Chat::$deleteMessageRules);
        // if the validator fails, redirect back to the form
        if ( $validator->fails() )
        {
            return Response::json(['status' => 'error', 'errorMessage' => 'The following validations are failed!',  'Data' => $validator->getMessageBag()->toArray() ]);
        }
        else
        {
            $user_id = $alldata['user_id'];
            $message_ids = $alldata['message_ids'];
            if( Chat::where('sender_id', '=', $user_id)->whereIn('id', $message_ids)->update(['dr_sender_id' => 1]) || Chat::where('receiver_id', '=', $user_id)->whereIn('id', $message_ids)->update(['dr_receiver_id' => 1]) )
            {
                return Response::json(['status' => 'success', 'SuccessMessage' => 'Messages deleted successfully!' ]);
            }
            else
            {
                return Response::json(['status' => 'error', 'errorMessage' => 'There is some problem occurred, please try again!']);
            }
        }
    }

    public function deleteChatConversation()
    {
        $alldata = json_decode(file_get_contents('php://input'), true);
        // run the validation rules on the inputs from the request
        $validator = Validator::make($alldata, Chat::$deleteChatConversationRules);
        
        // if the validator fails, redirect back to the form
        if ( $validator->fails() )
        {
            return Response::json(['status' => 'error', 'errorMessage' => 'The following validations are failed!',  'Data' => $validator->getMessageBag()->toArray() ]);
        }
        else
        {
            $user_id = $alldata['user_id'];
            $fan_ids = $alldata['fan_ids'];
            $chat1 = Chat::where('sender_id', $user_id)->whereIn('receiver_id', $fan_ids)->update(['dr_sender_id' => 1]);
            $chat2 = Chat::where('receiver_id', '=', $user_id)->whereIn('sender_id', $fan_ids)->update(['dr_receiver_id' => 1]); 
            if( $chat1 || $chat2 )
            {
                return Response::json(['status' => 'success', 'SuccessMessage' => 'Messages deleted successfully!' ]);
            }
            else
            {
                return Response::json(['status' => 'error', 'errorMessage' => 'There is some problem occurred, please try again!']);
            }
        }
    }
    
}