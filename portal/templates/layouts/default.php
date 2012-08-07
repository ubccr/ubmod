<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
  <title>UBMoD - Metrics on Demand</title>
  <link rel="stylesheet" href="<?php echo $BASE_URL ?>/resources/css/ext-all.css" type="text/css" media="screen" />
  <link rel="stylesheet" href="<?php echo $BASE_URL ?>/css/main.css" type="text/css" media="screen" />
  <script type="text/javascript" src="<?php echo $BASE_URL ?>/js/ext-all.js"></script>
  <script type="text/javascript">
    Ext.namespace('Ubmod');
    Ubmod.baseUrl = '<?php echo $BASE_URL ?>';
  </script>
  <script type="text/javascript" src="<?php echo $BASE_URL ?>/js/ubmod.js"></script>
</head>
<body>
<div class="header">
  <table style="width:100%;">
    <tr>
      <td><a href="<?php echo $BASE_URL ?>/"><img src="<?php echo $BASE_URL ?>/images/logo.png" style="border:0px;"/></a></td>
      <td valign="bottom" style="text-align:right;"><a href="<?php echo $BASE_URL ?>/about">About UBMoD</a></td>
    </tr>
  </table>
</div>
<div id="toolbarContainer" style="clear:both;" class="toolbar"><div id="toolbar"></div><div><span id="date-display"></span></div></div>
<div class="page">
  <table style="width:100%;">
    <tr>
      <td valign="top" style="width:230px;">
        <div class="menu">Menu<div class="menu-content">
          <ul id="menu-list">
            <li<?php if ($controller == "dashboard") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/dashboard">Dashboard</a></li>
            <li<?php if ($controller == "wait-time") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/wait-time">Wait Time</a></li>
            <li<?php if ($controller == "cpu-consumption") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/wall-time">Wall Time</a></li>
            <li<?php if ($controller == "user") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/user">User Detail</a></li>
            <li<?php if ($controller == "group") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/group">Group Detail</a></li>
            <li<?php if ($controller == "queue") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/queue">Queue Detail</a></li>
            <li<?php if ($controller == "tag") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/tag">Tag Management</a></li>
            <li<?php if ($controller == "tag") { echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/tag/keys">Tag Reports</a></li>
            <li<?php if ($controller == "about") {echo ' class="menu-active"'; } ?>><a href="<?php echo $BASE_URL ?>/about">About UBMoD</a></li>
          </ul>
        </div></div>
      </td>
      <td valign="top"><div id="content" class="content"><?php echo $content ?></div></td>
    </tr>
  </table>
</div>
<div class="footer"><div class="footer-text">
  <table align="center">
    <tr>
      <td><a href="http://ubmod.sf.net"><img src="<?php echo $BASE_URL ?>/images/ubmod_powered.png"/></a></td>
      <td><a href="http://www.ccr.buffalo.edu/">The Center for Computational Research</a><br/><a href="http://www.buffalo.edu">University at Buffalo, SUNY</a></td>
    </tr>
  </table>
</div></div>
<br/>
<br/>
</body>
</html>
