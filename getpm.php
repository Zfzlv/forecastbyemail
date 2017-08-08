<?php
set_time_limit(0);
ini_set('memory_limit','2000M');
ini_set('error_reporting','E_ERROR');
ini_set('date.timezone','Asia/Shanghai');
require_once('class.phpmailer.php');
$citys = array();
$citys_daimai = array();
$fp = fopen('globalcity.txt','r');
while(!feof($fp)){
	$str = fgets($fp);
	$str = preg_replace('/\r?\n/uis','',$str);
	if(!empty($str)){
		$abc = preg_split('/\t/',$str);
		$citys[$abc[0]][$abc[1]]= $abc[2];
		$citys_daimai[$abc[0]][$abc[1]]= $abc[3];
	}
}
fclose($fp);
$newday = 0;
$city_warn = array();
$icomfort = array(
		'9999'=>'',
		'4'=>'很热，极不适应',
		'3'=>'热，很不舒适',
		'2'=>'暖，不舒适',
		'1'=>'温暖，较舒适',
		'0'=>'舒适，最可接受',
		'-1'=>'凉爽，较舒适',
		'-2'=>'凉，不舒适',
		'-3'=>'冷，很不舒适',
		'-4'=>'很冷，极不适应'
);
while(true){
	$now = explode(":",date("H:i:s"));
	if($now[0]=="01"){
		$newday = 0;
		$city_warn = array();
	}
	if($now[0]>=8 && $now[0]<=19){
		$fp = fopen('users.txt','r');
		while(!feof($fp)){
			$str = fgets($fp);
			$str = preg_replace('/\r?\n/uis','',$str);
			if(!empty($str)){
				$abc = preg_split('/\t/',$str);
				$flag = 1;
				if(array_key_exists($abc[0],$citys)){
					if(!array_key_exists($abc[1],$citys[$abc[0]])){
						$flag = 3;
					}
				}else{
					$flag = 2;
				}
				if($flag==1){
					if($newday==0){
						$html = "";
						$url = $citys_daimai[$abc[0]][$abc[1]];
						$data = gets($url);
						$a = preg_replace('/^[\s\S]*?(今天[\s\S]*?<\/(tbody|div)>)[\s\S]*$/uis','$1',$data);
						if($a != $data){
							$b = array_empty(preg_split('/\s+/',strip_tags($a)));
							if(count($b)>6)
								$html .= $b[0].";".$b[1].";<br>白天：".$b[2].";最高温度：".$b[4].";".$b[6].";".$b[8].";<br>晚上：".$b[3].";最低温度：".$b[5].";".$b[7].";".$b[9]."<br>";
							else
								$html .= $b[0].";".$b[1].";<br>晚上：".$b[2].";最低温度：".$b[3].";".$b[4].";".$b[5]."<br>";
						}
						$url = 'http://www.nmc.cn/f/rest/real/'.$citys[$abc[0]][$abc[1]];
						$data = gets($url);
						if(!empty($data) && preg_match('/station/uis',$data)){
							$x = json_decode($data,true);
							$html .= "现在气温：".$x['weather']['temperature']."℃;体感温度：".$x['weather']['feelst']."℃;气压:".$x['weather']['airpressure']."hPa;降水:".$x['weather']['rain']."mm;相对湿度:".$x['weather']['humidity']."%;";
							if($x['wind']['direct']!="9999")$html .= $x['wind']['direct'].";";
							if($x['wind']['power']!="9999")$html .= $x['wind']['power'].";";
							$html .= $icomfort[$x['weather']['icomfort']]."<br>";
							if($x['warn']['issuecontent']!="9999"){
								$city_warn[$citys[$abc[0]][$abc[1]]]=$x['warn']['issuecontent'];
								$html .= $x['warn']['issuecontent']."<br>";
							}
							$url = "http://www.pm25.in/";
							$xx = preg_replace('/^[\s\S]*\/([^\/]*)/uis','$1',$citys_daimai[$abc[0]][$abc[1]]);
							$url .= $xx;
							$data = getsaqi($url);
							$a = preg_replace('/^[\s\S]*?(<div class="level"[\s\S]*?)<\/div>[\s\S]*$/uis','$1',$data);
							if(!empty($data) && $a != $data){
								$a = preg_replace('/&nbsp;?|\s+/uis','',strip_tags($a));
								$html .= $a.";";
								$b = preg_replace('/^[\s\S]*?(<div class="span12 data">[\s\S]*?)<div class="span1 more-city">[\s\S]*$/uis','$1',$data);
								if($b != $data){
									$b = preg_replace('/&nbsp;?|\s+/uis',' ',strip_tags($b));
									$b = array_empty(preg_split('/\s+/',$b));
									$html .= $b[1].":".$b[0].";".$b[3].":".$b[2]."μg/m3;"."<br>";
								}
								$b = preg_replace('/^[\s\S]*?(<div class="span12 caution">[\s\S]*?)<div class="station">[\s\S]*$/uis','$1',$data);
								if($b != $data){
									$b = preg_replace('/&nbsp;?|\s+/uis',' ',strip_tags($b));
									$b = array_empty(preg_split('/\s+/',$b));
									$html .= $b[0].":".$b[1].";<br>".$b[2].":".$b[3].";<br>".$b[4].":".$b[5].";"."<br>";
								}
							}
							$html='<html><head></head><body><p>尊贵的'.$abc[3].'!<br>'.$html.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;小小葵花田<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.date("Y-m-d").'</p></body></html>';
							sendemail("今日天气情况",$abc[2],$abc[3],$html);
						}
					}else{
						$html = "";
						$url = 'http://www.nmc.cn/f/rest/real/'.$citys[$abc[0]][$abc[1]];
						$data = gets($url);
						if(!empty($data) && preg_match('/station/uis',$data)){
							$x = json_decode($data,true);
							if(($now[0]=="12" && $now[0]=="00") || ($now[0]=="18" && $now[0]=="00")){
								$html .= "现在气温：".$x['weather']['temperature']."℃;体感温度：".$x['weather']['feelst']."℃;气压:".$x['weather']['airpressure']."hPa;降水:".$x['weather']['rain']."mm;相对湿度:".$x['weather']['humidity']."%;";
								if($x['wind']['direct']!="9999")$html .= $x['wind']['direct'].";";
								if($x['wind']['power']!="9999")$html .= $x['wind']['power'].";";
								$html .= $icomfort[$x['weather']['icomfort']]."<br>";
							}
							if($x['warn']['issuecontent']!="9999"){
								if((array_key_exists($citys[$abc[0]][$abc[1]],$city_warn) && $city_warn[$citys[$abc[0]][$abc[1]]]!=$x['warn']['issuecontent']) || !array_key_exists($citys[$abc[0]][$abc[1]],$city_warn)){
									$html .= $x['warn']['issuecontent']."<br>";
									$city_warn[$citys[$abc[0]][$abc[1]]] = $x['warn']['issuecontent'];
								}
							}
						}
						if(($now[0]=="12" && $now[1]=="00") || ($now[0]=="18" && $now[1]=="00")){
							/*$url = "http://www.pm25.com/city/";
							$xx = preg_replace('/^[\s\S]*\/([^\/]*)/uis','$1',$citys_daimai[$abc[0]][$abc[1]]);
							$url .= $xx;
							$data = getsaqi($url);
							$a = preg_replace('/^[\s\S]*?(<div class="citydata_banner_opacity[\s\S]*?)<div class="cbo_opacity"><\/div>[\s\S]*$/uis','$1',$data);
							if(!empty($data) && $a != $data){
								$a = preg_replace('/&nbsp;?/uis',' ',$a);
								$b = array_empty(preg_split('/\s+/',strip_tags($a)));
								$html .= $b[1].":".$b[0].";".$b[3].":".$b[2].";".$b[6].":".$b[4].$b[5].";".$b[7].";".$b[15].$b[16]."<br>";
							}*/
							$url = "http://www.pm25.in/";
							$xx = preg_replace('/^[\s\S]*\/([^\/]*)/uis','$1',$citys_daimai[$abc[0]][$abc[1]]);
							$url .= $xx;
							$data = getsaqi($url);
							$a = preg_replace('/^[\s\S]*?(<div class="level"[\s\S]*?)<\/div>[\s\S]*$/uis','$1',$data);
							if(!empty($data) && $a != $data){
								$a = preg_replace('/&nbsp;?|\s+/uis','',strip_tags($a));
								$html .= $a.";";
								$b = preg_replace('/^[\s\S]*?(<div class="span12 data">[\s\S]*?)<div class="span1 more-city">[\s\S]*$/uis','$1',$data);
								if($b != $data){
									$b = preg_replace('/&nbsp;?|\s+/uis',' ',strip_tags($b));
									$b = array_empty(preg_split('/\s+/',$b));
									$html .= $b[1].":".$b[0].";".$b[3].":".$b[2]."μg/m3;"."<br>";
								}
								$b = preg_replace('/^[\s\S]*?(<div class="span12 caution">[\s\S]*?)<div class="station">[\s\S]*$/uis','$1',$data);
								if($b != $data){
									$b = preg_replace('/&nbsp;?|\s+/uis',' ',strip_tags($b));
									$b = array_empty(preg_split('/\s+/',$b));
									$html .= $b[0].":".$b[1].";<br>".$b[2].":".$b[3].";<br>".$b[4].":".$b[5].";"."<br>";
								}
							}
						}
						if(!empty($html)){
							$html='<html><head></head><body><p>尊贵的'.$abc[3].'!<br>'.$html.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;小小葵花田<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.date("Y-m-d").'</p></body></html>';
							if(($now[0]=="12" && $now[1]=="00") || ($now[0]=="18" && $now[1]=="00")){
								sendemail("更新今日天气",$abc[2],$abc[3],$html);
							}else{
								sendemail("预警",$abc[2],$abc[3],$html);
							}
						}
					}
				}
			}
		}
		fclose($fp);
		$newday = 1;
	}
	sleep(59);
}

/*** 
  * 发送邮件 
  * @auth jie.li 
  */
function sendemail($subject,$email,$name,$html){
	$mail = new PHPMailer(); //实例化 
	$mail->IsSMTP(); // 启用SMTP 
	$mail->Host = "smtp.xxxx.com"; //SMTP服务器
	$mail->Port = 25;  //邮件发送端口 
	$mail->SMTPAuth   = true;  //启用SMTP认证 
	 
	$mail->CharSet  = "UTF-8"; //字符集 
	$mail->Encoding = "base64"; //编码方式 
	$mail->Username = "xxxx";  //你的邮箱 
	$mail->Password = "xxxx";  //你的密码 
	$mail->Subject = $subject; //邮件标题 
	$mail->From = "xxxx@xxxx.com";  //发件人地址（也就是你的邮箱） 
	$mail->FromName = "天气通报";  //发件人姓名 
	$mail->AddAddress($email, $name);
	//$mail->AddBCC("xxx@xxxx.com", "xxxx");
	//$mail->AddAttachment('xxxx','xxxxxx'); // 添加附件,并指定名称 
	$mail->IsHTML(true); //支持html格式内容 
	//$mail->AddEmbeddedImage("logo.jpg", "my-attach", "logo.jpg"); //设置邮件中的图片 
	$mail->Body = $html; //邮件主体内容 
	//发送 
	if(!$mail->Send()) { 
	  echo "Mailer Error: " . $mail->ErrorInfo; 
	} else { 
	  echo "Message sent!"; 
	} 
}
function gets($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, "http://www.nmc.cn/publish/forecast/ABJ/beijing.html"); 
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36");  
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_ENCODING, "gzip");
	curl_setopt($curl, CURLOPT_HTTPHEADER,array('Host: www.nmc.cn','Upgrade-Insecure-Requests: 1','Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8','Accept-Encoding:gzip, deflate, sdch','Accept-Language:zh-CN,zh;q=0.8','Connection:keep-alive'));
	curl_setopt($curl, CURLOPT_COOKIE,'JSESSIONID=50F439CFCEDDCED3FB60E20D82985844; UM_distinctid=15da6ba80bad8c-0db844a233f9e1-333f5902-232800-15da6ba80bb70b; followcity=54511%2C58367%2C59493%2C57516%2C58321%2C57679%2C58847%2C59287%2C58238; _gscu_1087957623=01740826ts1itf12; _gscs_1087957623=t017495502rux7k17|pv:5; _gscbrs_1087957623=1; CNZZDATA1254743953=1427195036-1501738014-%7C1501746172');  
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function getsaqi($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_REFERER, "http://www.pm25.in/shanghai"); 
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36");  
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_ENCODING, "gzip");
	curl_setopt($curl, CURLOPT_HTTPHEADER,array('Host: www.pm25.in','Upgrade-Insecure-Requests: 1','Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8','Accept-Encoding:gzip, deflate','Accept-Language:zh-CN,zh;q=0.8','Connection:keep-alive'));
	curl_setopt($curl, CURLOPT_COOKIE,'_aqi_query_session=BAh7B0kiD3Nlc3Npb25faWQGOgZFRkkiJWMyYjUyMjUyMGIwMjQyNGM3MTRiNTgwOGRjMWY5MmY3BjsAVEkiEF9jc3JmX3Rva2VuBjsARkkiMUpnNGZjQUM2VDY3ZjR1QnY1eFJobm04a1VVTkhCOGh1b3AvejUwVzJlN3c9BjsARg%3D%3D--767adf6265f3cd360c028b516d38d6250eeedc63; __utmt=1; __utma=162682429.430301522.1502103775.1502103775.1502103775.1; __utmb=162682429.7.10.1502103775; __utmc=162682429; __utmz=162682429.1502103775.1.1.utmcsr=baidu|utmccn=(organic)|utmcmd=organic');  
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function array_empty($a){
	$back=array();
	foreach($a as $v){
		if($v!=""){
			array_push($back,$v);
		}
	}
	return $back;
}
