var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var connection = require('./db-config');
var redis    = require('redis');

http.listen(4000, function(){
    console.log('listening on *:4000');
});

//var io         = require('socket.io')();
//var server     = io.listen(4000, { log: true });

var redisClient = redis.createClient();
var clientSockets = {};
var clientsCount = 0;

globalSocket = null;

//console.log('io: ', io);
//console.log('server: ', server);

redisClient.on("error", function (err) {
    console.log("Error " + err);
});

redisClient.on("connect", function () {

    console.log('redis server connected');

    //subscribe channels for updates...
    redisClient.subscribe('like');
    redisClient.subscribe('comment');
    redisClient.subscribe('best_at');
    redisClient.subscribe('chat_message');
    redisClient.subscribe('fan');
    redisClient.subscribe('user_event');
    redisClient.subscribe('weekly_challenge');
});

function sendData(eventName, responseData)
{
    var data = JSON.parse(responseData);
    var eventReceivers = data.notificationReceivers;

    delete data.notificationReceivers;

    eventReceivers.forEach(function(receiver) {
        var socket_id = clientSockets[receiver];
        io.sockets.to(socket_id).emit(eventName, data);
    });
}

redisClient.on("message", function(channel, data) {

    console.log('redisClient inside');

    switch (channel)
    {
        case 'like':
        {
            console.log('Like Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
        case 'comment':
        {
            console.log('Comment Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
        case 'best_at':
        {
            console.log('BestVote Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
        case 'chat_message':
        {
            console.log('Message Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
        case 'fan':
        {
            console.log('Fan Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
        case 'user_event':
        {
            console.log('Event Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
        case 'weekly_challenge':
        {
            console.log('Event Event - Redis message : ', channel);
            sendData(channel, data);
            break;
        }
    }
});

io.on('connection', function(socket) {

    var user_id = parseInt(socket.handshake.query.token);

    connection.query('SELECT id from users where id='+ user_id, function(err, rows, fields) {

        if ( rows.length > 0 )
        {
            clientSockets[user_id] = socket.id;
            clientsCount = Object.keys(clientSockets).length;

            socket.emit('init', {message: 'verified'});

            console.log('ClientSockets: ', clientSockets);
            console.log('total clients connected: ', clientsCount);

            //console.log('socket object: ', socket);

            globalSocket = socket;
        }
        else
        {
            socket.emit('init', {message: 'rejected'});
            socket.disconnect();
        }
    });
    connection.release();
    //when socket disconnected...
    socket.on('disconnect', function() {
        console.log('user disconnected');
    });
});

//console.log('server: ',  server.rooms);

/*var clientSockets = {};
var clientsCount = 0;

function sendData(user, data, clientSockets)
{
  for(var single_user in user)
  {
    console.log('single_user: ', data[user[single_user]]);
    //send a particular socket...accroding to the user id...
  }
}

server.sockets.on('connection', function(socket) {  
    
    socket.emit('connected', {msg: 'connected'});
    
    console.log('Connection Event');
    
    var user_id = socket.manager.handshaken[socket.id].query.user_id;
    clientSockets[user_id] = socket.id;
    clientsCount = Object.keys(clientSockets).length;

//////////////////////////////////////////////////////////////////register///////////////////////////////////////////////////////
    socket.on('register', function(data) {
        console.log('register event');
        console.log('data: ', data);

        var uid = data.user_id;

        //socket.join(uid.toString());

        connection.query('SELECT id from user where id='+ uid, function(err, rows, fields){

            if( rows.length > 0 )
            {
                socket.emit('register', {msg: 'verified'});
                //clientSockets[uid] = socket.id;
                //console.log('clientSockets', clientSockets);
                //console.log('total clients connected: ', clientsCount);
            }
            else
            {
                socket.emit('register', {msg: 'rejected'}); 
                socket.disconnect();
            }
        });
    });
//////////////////////////////////////////////////////////////////register///////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////FindOpponent///////////////////////////////////////////////////////
    socket.on('findPlayerOpponent', function(params){
        
        console.log('FindPlayerOpponent Params: ', params);
        
        var paramsToCheck = ['user_id', 'type', 'type_id', 'language', 'is_cancel'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'findPlayerOpponent') )
        {
            if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'findPlayerOpponent') ) 
            {
                var language = parseInt(params.language);
                var user_id = parseInt(params.user_id);
                var type = parseInt(params.type);
                var type_id = parseInt(params.type_id);
                var is_cancel = params.is_cancel;

                questions.findOpponent(user_id, type, type_id, language, is_cancel, function(users, data){
                    
                    console.log('FindOpponent Users: ', users);
                    console.log('FindOpponent Data: ', data);

                    server.sockets.socket(clientSockets[users.userId]).emit('findPlayerOpponent', data[users.userId]);
                });
            }
        }
    });
//////////////////////////////////////////////////////////////////FindOpponent//////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////IndividualQuestionResult///////////////////////////////////////////////////
    socket.on('individualQuestionResult', function(params){
          
        console.log('IndividualQuestionResult Params:', params);

        var paramsToCheck = ['type', 'current_points', 'q_option_id', 'opponent_id', 'type_id', 'user_id', 'question_id', 'is_correct', 'seconds', 'opponent_points', 'contest_id'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'individualQuestionResult') )
        {
           // if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'individualQuestionResult') ) 
            //{
                var type            = parseInt(params.type);
                var current_points  = parseInt(params.current_points);
                var q_option_id     = parseInt(params.q_option_id);
                var opponent_id     = parseInt(params.opponent_id);
                var type_id         = parseInt(params.type_id);
                var user_id         = parseInt(params.user_id);
                var question_id     = parseInt(params.question_id);
                var is_correct      = parseInt(params.is_correct);
                var seconds         = parseInt(params.seconds);
                var opponent_points = parseInt(params.opponent_points);
                var contest_id      = parseInt(params.contest_id);

                indQResult.individualQuestionResult(user_id, question_id, q_option_id, contest_id, is_correct, current_points, seconds, opponent_id, opponent_points, type, type_id, function(users, data){

                    console.log('IndividualQuestionResult Users: ', users);
                    console.log('IndividualQuestionResult Data: ', data);
                    console.log('Challenge Id: ', challengeId);

                        //for challenge purposes.. room is created on the base of challeng_id and data sent to the both parties...
                        if( params.challenge_id !== undefined )
                        {
                                var challengeId = parseInt(params.challenge_id);
                                server.sockets.in(challengeId).emit('individualQuestionResult', data);      //note: but it was also working without challenge_id provided strange...
                        }
                        else
                        {
                        for(var user in users)
                        {
                            if( users[user] !== undefined )  //here need to replace a function... helper.isBot..
                            {
                                server.sockets.socket(clientSockets[users[user]]).emit('individualQuestionResult', data);    
                            }                
                        }              
                        }
                });
            //}
        }
    });
/////////////////////////////////////////////////////////////////IndividualQuestionResult///////////////////////////////////////////////////


///////////////////////////////////////////////////////////////notifyOnSurrender////////////////////////////////////////////////////////////
    socket.on('notifyOnSurrender', function(params){
      
        console.log('NotifyOnSurrender Params: ', params);
        
        var paramsToCheck = ['user_id', 'contest_id', 'opponent_id'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'notifyOnSurrender') )
        {
           // if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'notifyOnSurrender') ) 
            //{
                var userId = parseInt(params.user_id);
                var contestId = parseInt(params.contest_id);
                var opponentId = parseInt(params.opponent_id);
                  
                var response = {};
                response.flag = 4;
                response.message = message.success;
                response.againstContest = contestId; 

                console.log('NotifyOnSurrender Data: ', response);

                //for challenge purposes.. room is created on the base of challeng_id and data sent to the both parties...
                if( params.challenge_id !== undefined )
                {
                    var challengeId = parseInt(params.challenge_id);
                    server.sockets.in(challengeId).emit('notifyOnSurrender', response);
                }
                else
                {
                    server.sockets.socket(clientSockets[opponentId]).emit('notifyOnSurrender', response);
                }
            //}
        }
    });
///////////////////////////////////////////////////////////////notifyOnSurrender////////////////////////////////////////////////////////////

  
    socket.on('sendChallenge', function(params){

        console.log('SendChallenge Params: ', params);

        var paramsToCheck = ['user_id', 'friend_id', 'type', 'type_id', 'language'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'sendChallenge') )
        {
           // if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'sendChallenge') ) 
            //{
                var userId = parseInt(params.user_id);
                var friendId = parseInt(params.friend_id);
                var type = parseInt(params.type);
                var typeId = parseInt(params.type_id);
                var language = parseInt(params.language);

                challenge.sendChallenge(userId, friendId, type, typeId, language, function(users, data){
                    
                    console.log('SendChallenge Users: ', users);
                    console.log('SendChallenge Data: ', data);

                    var roomName = data[userId].challenge_id;
                    socket.join(roomName);

                    socket.emit('sendChallenge', data);
                });
           // }
        }
    });

    socket.on('acceptChallenge', function(params){
        
        console.log('AcceptChallenge Params: ', params);
        
        var paramsToCheck = ['user_id', 'challenge_id', 'language'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'acceptChallenge') )
        {
            if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'acceptChallenge') ) 
            {
                var userId = parseInt(params.user_id);
                var challengeId = parseInt(params.challenge_id);
                var language = parseInt(params.language);

                console.log('challengeid: ', challengeId);
                challenge.acceptChallenge(userId, challengeId, language, function(users, data){
                    
                    var user = users.userId;
                    var opponent = users.opponentId;
                    socket.join(challengeId);

                    console.log('AcceptChallenge users: ', users);
                    console.log('AcceptChallenge Data: ', data[user]);
                    
                    server.sockets.in(challengeId).emit('acceptChallenge', data);
                });
            }
        }
    });

    socket.on('reMatch', function(params){

        console.log('ReMatch Params: ', params);

        var paramsToCheck = ['user_id', 'contest_id', 'language'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'reMatch') )
        {
            if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'reMatch') ) 
            {
                var userId    = parseInt(params.user_id); 
                var contestId = parseInt(params.contest_id);
                var language  = parseInt(params.language);

                challenge.reMatch(userId, contestId, language, function(users, data){

                    console.log('ReMatch users: ', users);
                    console.log('ReMatch Data: ', data);

                    var roomName = data[users.userId].challenge_id;
                    socket.join(roomName);

                    socket.emit('reMatch', data);
                });
            }
        }
    });

    socket.on('cancelChallenge', function(params){
        
        console.log('CancelChallenge Params: ', params);

        var paramsToCheck = ['user_id', 'challenge_id'];

        if( helper.verifyClientRequiredParams(params, paramsToCheck, socket, 'cancelChallenge') )
        {
            //if( helper.verifyClientExtraParams(params, paramsToCheck, socket, 'cancelChallenge') ) 
            //{
                var userId = parseInt(params.user_id);
                var challengeId = parseInt(params.challenge_id);

                //both users and data are objects... 
                challenge.cancelChallenge(userId, challengeId, function(users, data){
                    
                    console.log('CancelChallenge users: ', users);
                    console.log('CancelChallenge Data: ', data);

                    var user = users.userId;

                    //writing data to the user socket...
                    server.sockets.socket(clientSockets[user]).emit('cancelChallenge', data[user]);
                });
            //}
        }
    });

    socket.on('disconnect', function(){
        console.log('user disconnected');
        console.log('server rooms disconnected: ', server.rooms);
    });
});*/