#!/usr/bin/php
<?php

require_once('autoload.php');

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;
use Telegram\Bot\Api;

class Minimal extends CLI
{
    // register options and arguments
    protected function setup(Options $options)
    {
        $options->setHelp("SITE BOT Commandline tools.\nThis tools included with telegram integration.\n DEVELOPED BY \n- Fakhri Nurfauzan [fakhrinf@hotmail.com]");
        $options->registerOption('version', 'print version', 'v');
        $options->registerOption('getme', 'Testing bot auth token');
        $options->registerOption('getupdates', 'Get telegram bot update');

        $options->registerCommand('startbot', 'Start bot to listen to user.');

        $options->registerCommand('sendmessage', 'Send Message to telegram user');
        $options->registerOption('chatid', 'Set user chat id', 'u', 'chatid','sendmessage');
        $options->registerOption('message', 'Set Message', 'm', 'message','sendmessage');

        $options->registerArgument('chatid', 'user chat id', false);
        $options->registerArgument('message', 'message content', false);
    }

    // implement your code
    protected function main(Options $options)
    {
        date_default_timezone_set('Asia/Jakarta');
        $cli = new League\CLImate\CLImate;
        $telegram = new Api('580738273:AAHWWEA7iNQZuvk1XxQ2AMw3zbVg1iBFCjI');
        $usernamebot= "@sitetower_bot";

        if ($options->getOpt('version')) {
            $this->info('1.0.0');
        } else if($options->getOpt('getme')){
            $response = $telegram->getMe();

            $botId = $response->getId();
            $firstName = $response->getFirstName();
            $username = $response->getUsername();

            $cli->table(array(array("id" => $botId, "First Name" => $firstName, "User Name" => $username)));
        } else if($options->getOpt('getupdates')){

            $response = $telegram->getUpdates();
            $cli->lightCyan()->json($response);

        } else if($options->getCmd() == "sendmessage") {

            $user = $options->getOpt("chatid", "0");
            $message = $options->getOpt("message", "...");

            if ($user != 0) {
                $response = $telegram->sendMessage([
                'chat_id' => $user, 
                'text' => $message
                ]);

                $messageId = $response->getMessageId();
                $cli->green()->flank('Message Send.', '#');
            }else{
                $cli->red()->out("You need to pass user chat id.\nuse --help/-h for more information.");
            }

        } else if($options->getCmd() == "startbot") {

            while (true) {
                global $debug;
                $update_id  = 0;
                echo "-";
            
                if (file_exists("last_update_id")) 
                    $update_id = (int)file_get_contents("last_update_id");
            
                $url = $telegram->getUpdates(["offset" => $update_id]);
                $result = json_decode(json_encode($url), true);
                $updates = $result;
                // jika debug=0 atau debug=false, pesan ini tidak akan dimunculkan
                if ((!empty($updates)) and ($debug) )  {
                    echo "\r\n===== isi diterima \r\n";
                    print_r($updates);
                }
            
                foreach ($updates as $message)
                {
                    echo '+';
                    $updateid = $message["update_id"];
                    $message_data = $message["message"];
                    if (isset($message_data["text"])) {
                        $chatid = $message_data["chat"]["id"];
                        $message_id = $message_data["message_id"];
                        $text = $message_data["text"];

                        // inisiasi variable hasil yang mana merupakan hasil olahan pesan
                        $hasil = '';  
                        $fromid = $message_data["from"]["id"]; // variable penampung id user
                        $chatid = $message_data["chat"]["id"]; // variable penampung id chat
                        $pesanid= $message_data['message_id']; // variable penampung id message
                        // variable penampung username nya user
                        isset($message_data["from"]["username"])
                            ? $chatuser = $message_data["from"]["username"]
                            : $chatuser = '';
                        
                        // variable penampung nama user
                        isset($message_data["from"]["last_name"]) 
                            ? $namakedua = $message_data["from"]["last_name"] 
                            : $namakedua = '';   
                        $namauser = $message_data["from"]["first_name"]. ' ' .$namakedua;
                        // ini saya pergunakan untuk menghapus kelebihan pesan spasi yang dikirim ke bot.
                        $textur = preg_replace('/\s\s+/', ' ', $text); 
                        // memecah pesan dalam 2 blok array, kita ambil yang array pertama saja
                        $command = explode(' ',$textur,2); //
                        // identifikasi perintah (yakni kata pertama, atau array pertamanya)
                        $reply_markup = $telegram->replyKeyboardHide();
                        switch ($command[0]) {
                            case '/start':
                                $hasil = "Hallo $namauser, \n";
                                break;
                            // jika ada pesan /id, bot akan membalas dengan menyebutkan idnya user
                            case '/address':
                                if(isset($command[1])){
                                    require_once("dbcon/db_site.php");
                                    $siteid = strtoupper($command[1]);
                                    $stmt = $pdo->prepare("SELECT * FROM site_profil where site_id=?");
                                    $stmt->bindParam(1, $siteid);
                                    $stmt->execute();
                                    $plist = $stmt->fetchAll();

                                    if (!empty($plist)) {
                                        $address = $plist[0]['alamat'];
                                        $sitename = $plist[0]['site_name'];
                                        $hasil = "SITE ID: {$siteid}\nName:{$sitename}\nAddress:{$address}";
                                    }else{
                                        $hasil = "I'm sorry but i cant find this site id: {$siteid}";
                                    }
                                }else{
                                    $hasil = "Please type SITE ID in following sentence";
                                }
                                break;
                            default:
                                $hasil = "I'm afraid i dont understand that";
                                break;
                        }

                        $response = $hasil;
                        $rm=$reply_markup;
                        if (!empty($response)){
                            $response = $telegram->sendMessage([
                            'chat_id' => $chatid, 
                            'text' => $response,
                            'reply_markup' => $rm
                            ]);

                            $messageId = $response->getMessageId();
                        }
                    }
                    // return $updateid;
                    file_put_contents("last_update_id", $updateid + 1);
                }
                
                // update file id, biar pesan yang diterima tidak berulang
                sleep(1);
            }

        } else {
            echo $options->help();
        }
    }

    private function postmessage($cli, $telegram, $user, $message) {

        if ($user != 0) {
            $response = $telegram->sendMessage([
            'chat_id' => $user, 
            'text' => $message
            ]);

            $messageId = $response->getMessageId();
            $cli->green()->flank('Message Send.', '#');
        }else{
            $cli->red()->out("You need to pass user chat id.\nuse --help/-h for more information.");
        }
    }
}
// execute it
$cli = new Minimal();
$cli->run();

?>