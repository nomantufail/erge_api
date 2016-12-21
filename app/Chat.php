<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model {

	protected $table = 'chats';

    public static $sendMessageRules = ['user_id' => 'required|integer', 'receiver_id' => 'required|integer', 'chat_message' => 'max:500'];
    public static $deleteMessageRules = ['user_id' => 'required|integer', 'message_ids' => 'required'];
    public static $deleteChatConversationRules = ['user_id' => 'required|integer', 'fan_ids' => 'required'];
    public static $viewMessageRules = ['user_id' => 'required|integer', 'fan_id' => 'required'];

}
