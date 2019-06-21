<?php

//连接数据库
$conn = new mysqli("192.168.253.51","root","zhaokai123","a1809");
$conn->set_charset("utf8");

$server = new swoole_websocket_server('0.0.0.0',9502);

$server->on('open',function($server,$req) use($conn){

});
$server->on('message', function($server, $frame) use ($conn) {
    echo "received message: {$frame->fd}\n";
    $fd = $frame->fd;
    $store_id = rand(1,20);

    $arr = json_decode($frame->data,true);
    if(!$arr){
        $sql = "insert into chat(uname,store_id)values('$frame->data','$store_id')";
        $res = $conn->query($sql);
        if($res) {
            foreach ($server->connections as $k) {
                if($k!=$frame->fd){
                    $server->push($k, json_encode(['fd'=>$fd,'uname'=>$frame->data]));
                }
            }
        }
    }else{
        $uname = $arr['uname'];
        $content = $arr['content'];
        $time = time();
        $sql = "insert into chat(uname,content,send_time,store_id)values('$uname','$content','$time','$store_id')";
        $res = $conn->query($sql);
        $to  = $arr['to'];
        if($res) {
            if($to){
                $content1 = explode(':',$content);
                foreach ($server->connections as $k) {
                    if($k==$to||$k==$fd){
                        $server->push($k, json_encode(['fd'=>$fd,'uname'=>$uname,'content'=>$content1[1]]));
                    }
                }
            }else{
                foreach ($server->connections as $k) {
                        $server->push($k, json_encode(['fd'=>$fd,'uname'=>$uname,'content'=>$content]));
                }
            }
        };
    }
});

$server->on('close', function($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();