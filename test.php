<?php

// csvを取得
$csv = file_get_contents('https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv');

//-- UTF8へ変換
$csv = mb_convert_encoding($csv, 'UTF-8', 'SJIS-win');

//-- 正規表現で抜き出す
if (!preg_match_all('/(\d{4})\/(\d{1,2})\/(\d{1,2}),(.+)/u', $csv, $matchAllAry, PREG_SET_ORDER)) {
    die('Error');
}

$holidayAry = [];

foreach ($matchAllAry as $matchAry) {
    list( , $year, $month, $day, $name) = $matchAry;

    $tmpAry = [$year,$month,$day];

    $holidayAry[(int) $year][(int) $month][(int) $day] = trim($name);
}


// タイムゾーンを設定
date_default_timezone_set('Asia/Tokyo');

// 前月・次月リンクが押された場合は、GETパラメーターから年月を取得
if (isset($_GET['ym'])) {
    $ym = $_GET['ym'];
} else {
    // 今月の年月を表示
    $ym = date('Y-m');
}

//echo $ym; 2021-03

// タイムスタンプを作成し、フォーマットをチェックする

$timestamp = strtotime($ym . '-01');
if ($timestamp === false) {
    $ym = date('Y-m');
    //2021-03
    $timestamp = strtotime($ym . '-01');
}


// 今日の日付 フォーマット 例）2018-07-3
$today = date('Y-m-j');

// カレンダーのタイトルを作成 例）2017年7月
$html_title = date('Y年n月', $timestamp);

// 前月・次月の年月を取得
// 方法１：mktimeを使う mktime(hour,minute,second,month,day,year)
$prev = date('Y-m', mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp)));
$next = date('Y-m', mktime(0, 0, 0, date('m', $timestamp)+1, 1, date('Y', $timestamp)));

// 方法２：strtotimeを使う
// $prev = date('Y-m', strtotime('-1 month', $timestamp));
// $next = date('Y-m', strtotime('+1 month', $timestamp));

// 該当月の日数を取得
$day_count = date('t', $timestamp);

//date関数の引数
// t：指定した月の日数
// w：曜日番号（0[日曜]から6[土曜]の値）

// １日が何曜日か 0:日 1:月 2:火 ... 6:土
// 方法１：mktimeを使う
$youbi = date('w', mktime(0, 0, 0, date('m', $timestamp), 1, date('Y', $timestamp)));
// 方法２
// $youbi = date('w', $timestamp);

// カレンダー作成の準備
$weeks = [];
$week = '';

// 第１週目：空のセルを追加
// 例）１日が水曜日だった場合、日曜日から火曜日の３つ分の空セルを追加する
$week .= str_repeat('<td></td>', $youbi);

for ( $day = 1; $day <= $day_count; $day++,$youbi++) {

    $date = $ym . '-' . $day;
    // 2021-03-3


    $ndate = (explode("-",$date));
    $holiday_year = $ndate[0];
    $holiday_month = $ndate[1];
    $holiday = $holidayAry[(int)$holiday_year][(int)$holiday_month][(int)$day];
    //祝日を定義
    
    if ($today == $date && !empty($holiday)) {

        $week .= '<td class="today holiday">' . $day . " ".$holiday;
    }elseif ($today == $date) {
        $week .= '<td class="today">' . $day;
    } elseif(!empty($holiday)){
        $week .= '<td class="holiday">' . $day . " ".$holiday;
    } else {
        $week .= '<td>' . $day;
    }
    $week .= '</td>';

    // 週終わり、または、月終わりの場合
    if ($youbi % 7 == 6 || $day == $day_count) {

        if ($day == $day_count) {
            // 月の最終日の場合、空セルを追加
            // 例）最終日が木曜日の場合、金・土曜日の空セルを追加
            $week .= str_repeat('<td></td>', 6 - ($youbi % 7));
        }

        // weeks配列にtrと$weekを追加する
        $weeks[] = '<tr>' . $week . '</tr>';

        // weekをリセット
        $week = '';
	}
}






?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>PHPカレンダー</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans+JP" rel="stylesheet">
    <style>
        .container {
            font-family: 'Noto Sans JP', sans-serif;
            margin-top: 80px;
        }
        h3 {
            margin-bottom: 30px;
        }
        th {
            height: 30px;
            text-align: center;
        }
        td {
            height: 100px;
        }
        .today {
            background: orange;
        }
        th:nth-of-type(1), td:nth-of-type(1), .holiday {
            color: red;
        }
        th:nth-of-type(7), td:nth-of-type(7) {
            color: blue;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3><a href="?ym=<?php echo $prev; ?>">&lt;</a> <?php echo $html_title; ?> <a href="?ym=<?php echo $next; ?>">&gt;</a></h3>
        <table class="table table-bordered">
            <tr>
                <th>日</th>
                <th>月</th>
                <th>火</th>
                <th>水</th>
                <th>木</th>
                <th>金</th>
                <th>土</th>
            </tr>
            <?php
                foreach ($weeks as $week) {
                    echo $week;
                }
            ?>
        </table>
    </div>
</body>
</html>