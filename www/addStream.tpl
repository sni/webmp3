<!-- $Id: addStream.tpl,v 1.1 2005/06/16 13:13:00 sven Exp $ -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta http-equiv="expires" content="0">
  <meta name="author" content="Sven Nierlein">
  <meta name="publisher" content="Sven Nierlein">
  <meta name="copyright" content="Sven Nierlein">
  <meta name="description" content="">
  <meta name="keywords" content="">
  <link rel="stylesheet" href="style.css">
  <title>WebMP3s</title>
</head>
<body>
    add stream to playlist
    <form action="index.php" method="POST">
        <input type="text" name="name" value"" size=35>
        <input type="hidden" name="action" value="doAddStream">
        <input type="submit" name="submit" value="add">
    </form>
</body>
</html>
