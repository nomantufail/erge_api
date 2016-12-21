var mysql      = require('mysql');

var connection = mysql.createConnection({
  host     : 'localhost',
  database : 'erge', 
  user     : 'root',
  password : ''
});

connection.connect(function(err){
  if (err)
  {
      console.error('Database Connection Error: ' + err.stack);
  }
  else
  {
      console.log('Database Connected: Connection id: ' + connection.threadId);
  }
});
    
module.exports = connection;
