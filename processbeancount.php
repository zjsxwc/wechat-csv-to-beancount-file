<?php
$file = __DIR__ . "/微信对账-IN.csv";

$content = file_get_contents($file);

$lines = explode("\n", $content);
$bankList = [];
$moneyReceiverList = [];
$beancountItemTxtList = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (!$line) {
        continue;
    }
    if (is_numeric($line[0])) {

        START:
//        var_dump($line);
        $segList = explode(",", $line);
        if ($segList[6] === "¥3.00") {
           $line = "2024-08-04 09:08:00,商户消费,上海新上铁实业发展集团有限公司,自贩机消费:怡宝饮用纯净水555ml,支出,¥3.00,浙江xxx银行信用卡(0001),支付成功,0000,,/";
            $segList = explode(",", $line);
        }

        $sj = $segList[0];
        $date = explode(" ", $sj)[0];
        $type = $segList[4];
//        var_dump($type);
        $money = $segList[5];
        $cp = mb_strpos($money, "¥");
        $moneyVal = mb_substr($money, $cp+1);
        $moneyVal = floatval($moneyVal);
        if ($type === "支出") {
            $moneyVal = -1 * $moneyVal;
        } else if ($type !== "收入") {
            if ($type === "/") {
                continue;
            }

            // 使用正则表达式匹配引号中的内容并进行处理
            $line = preg_replace_callback('/"([^"]*)"/', function ($matches) {
                $originalString = $matches[1];
                $replacedString = str_replace(array(',', '"'), '', $originalString);
                return '"'. $replacedString. '"';
            }, $line);

            var_dump($type, $line, $moneyVal);
            goto START;
        }
        $moneyReceiver = $segList[2];
        if ($moneyReceiver === "/") {
            $moneyReceiver = "零钱";
        }
        $moneyReceiver = str_replace("(","", $moneyReceiver);
        $moneyReceiver = str_replace(")","", $moneyReceiver);
        $moneyReceiver = str_replace(" ","", $moneyReceiver);
        $moneyReceiver = str_replace(" ","", $moneyReceiver);
        $moneyReceiver = str_replace("•","-", $moneyReceiver);
        $moneyReceiver = str_replace("~","-", $moneyReceiver);
        $moneyReceiver = str_replace("\"","", $moneyReceiver);
        $moneyReceiver = str_replace("@","", $moneyReceiver);
        $moneyReceiver = str_replace("-","", $moneyReceiver);
        $moneyReceiver = str_replace("：","", $moneyReceiver);
        $moneyReceiver = str_replace("，","", $moneyReceiver);
        $moneyReceiver = str_replace(".","。", $moneyReceiver);
        $moneyReceiver = str_replace("_","", $moneyReceiver);
        $moneyReceiver = ucfirst($moneyReceiver);
        if (!$moneyReceiver) {
            $moneyReceiver = "未命名";
        }
        if (!in_array($moneyReceiver, $moneyReceiverList)) {
            $moneyReceiverList[] = $moneyReceiver;
        }
        $desc = $segList[2];
        $desc = str_replace("(","", $desc);
        $desc = str_replace(")","", $desc);
        $desc = str_replace(" ","", $desc);
        $desc = str_replace(" ","", $desc);
        $desc = str_replace("•","-", $desc);
        $desc = str_replace("~","-", $desc);
        $desc = str_replace("\"","", $desc);
        $desc = str_replace("-","", $desc);


        $bank = $segList[6];
        if (!$bank) {
            $bank = "未命名";
        }
        if ($bank === "/") {
            $bank = "零钱";
        }
        $bank = ucfirst($bank);

        $bank = str_replace("(","（", $bank);
        $bank = str_replace(")","）", $bank);
        $bank = str_replace(" ","", $bank);
        $bank = str_replace(" ","", $bank);
        $bank = str_replace("•","-", $bank);
        if (!in_array($bank, $bankList)) {
            $bankList[] = $bank;
        }

        $beancountItemTxt = <<<TXT

{$date} * "{$desc}"
   Assets:Bank:{$bank} {$moneyVal} CNY
   Expenses:Payment:{$moneyReceiver}
TXT;
        $beancountItemTxtList[] = $beancountItemTxt;
    }
}

$bcTxt = <<<TXT
option "title" "个人记账例子"
option "operating_currency" "CNY"


TXT;
foreach ($bankList as $bank) {
    $bcTxt .= "2024-01-01 open Assets:Bank:" . $bank . "\n";
}
$bcTxt .= "\n";

foreach ($moneyReceiverList as $moneyReceiver) {
    $bcTxt .= "2024-01-01 open Expenses:Payment:" . $moneyReceiver . "\n";
}
$bcTxt .= "\n";

foreach ($beancountItemTxtList as $beancountItemTxt) {

    $bcTxt .= $beancountItemTxt . "\n";

}

file_put_contents(__DIR__ . "/out.beancount", $bcTxt);
