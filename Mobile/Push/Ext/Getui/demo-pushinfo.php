<?php
header("Content-Type: text/html; charset=utf-8");

require_once(dirname(__FILE__) . '/' . 'igetui/utils/ApnsUtils.php');

getPushInfoLen();


function getPushInfoLen() {
    $rep = ApnsUtils :: validatePayloadLength("近日，房祖名因为吸毒被抓引起了不小的震荡。不少厂商也由此撤销代言广告。近日又有台湾媒体报道称，房祖名原定于8月18日参加某真人秀节目录影，但因为被抓未能出席，遭节目制作方索赔2500万(约500万人民币)元违约金。另外，谢霆锋的师妹洛诗和常一娇因吸毒被抓的消息得到证实。",
						"", "b", "a", "", "4", "com.gexin.ios.silence", "DDDD",0);
    var_dump($rep);
    echo ("<br><br>");
}

?>
