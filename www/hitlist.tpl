<!-- $Id: hitlist.tpl,v 1.5 2005/09/20 08:48:17 sven Exp $ -->
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
<body<!--php: reload -->>
<a href="index.php?action=hitlist&view=all">view all</a> - <a href="index.php?action=clearHitlist">clear hitlist</a>
<hr>
most played tracks:<br><hr>
<table border=2 cellpadding=2 cellspacing=0>
  <tr>
    <th>nr</th>
    <th>played</th>
    <th>&nbsp;</th>
    <th>track</th>
  </tr>
<!--php_start: hitlist -->
  <tr>
    <td>[x]</td>
    <td align="center">[num]</td>
    <td><a href="index.php?action=addFile&file=[name]">add</a></td>
    <td nowrap>[name]</td>
  </tr>
<!--php_end: hitlist -->
</table>
<center>
<a href="" onClick="window.close()">close</a>
</center>
</body>
</html>
