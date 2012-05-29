<?php
require_once('../../config.inc.php');
require_once(ROOT_DIR . 'classes/NewsTable.inc.php');
require_once(ROOT_DIR . 'admin/modules/auth.inc.php');

if (isset($debug)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
$nTable = new NewsTable();
$html = "<html>
<a href=\"add_news.php\">Add a News Item</a></br>";


    $i = 0;
    $nList = $nTable->GetNews();
    $html .= "<table border=\"1\">";
    $html .= "<tr><th>Title</th><th>Body</th><th>Date</th><th>User</th></tr>";
    foreach ($nList as $row) {
        $html .= "<tr><td><a href=\"" . HOME;
        $html .= "admin/modules/edit_news.php?nid={$row['nid']}\">{$row['title']}</a></td>";
        $html .= "<td>{$row['body']}</td>";
        $html .= "<td>{$row['date']}</td>";
        $html .= "<td><a href=\"" . HOME;
        $html .= "admin/modules/edit_user.php?nid={$row['uid']}\">{$row['uname']}</a></td>";
        $html .= "</tr>";
    }
    $html .= "</table>";

$html .= "</html>";
echo $html;
?>