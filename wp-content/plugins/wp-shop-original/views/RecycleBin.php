<?php 
  
$user = wp_get_current_user();
$role = array_shift($user->roles);

if ($role =="administrator"||$role =="Merchant") {
  $session_id = session_id();
  $full_path=get_option("wpshop.cartpage",get_bloginfo('wpurl')."/cart");
  
  $search = strripos($full_path, '?');
  if ($search === false) {
    $new_url = $full_path.'?secret='.$session_id;
  }else {
    $new_url = $full_path.'&secret='.$session_id;
  }
  
  echo "<div id=\"pass_order\" style=\"margin-bottom:15px;\"><p>".__('You can transfer the order to another device by clicking on this link:', 'wp-shop')." </p>".$new_url."</div>";
  echo "<a class=\"wpshop-button wpshop_reset_order\">".__('Reset the order on this device', 'wp-shop')."</a>";
}
  

if ($this->dataSend) {
	$this->render("RecycleBinAfterSend.php");
	return;
}

?>

<script type="text/javascript">
<?php   if (is_user_logged_in()&&isset($_GET['payment'])&&$_GET['payment']){ ?>
	jQuery(function($) {
	<?php 
	$form = Wpshop_Forms::getInstance()->getFormByName("wpshop-" . $_GET['payment']);
	global $current_user;
	

	
	foreach($form['fields'] as $field) {

	if ($field['type'] == "Name") {
			echo "$('[name=\"{$field['postName']}\"]').val('{$current_user->first_name} {$current_user->last_name}');";
		}
		if ($field['type'] == "Phone") {
			echo "$('[name=\"{$field['postName']}\"]').val('{$current_user->phone}');";
		}
		if ($field['type'] == "Address") {
			echo "$('[name=\"{$field['postName']}\"]').val('{$current_user->address}');";
		}
		if ($field['email']) {
			echo "$('[name=\"{$field['postName']}\"]').val('{$current_user->user_email}');";
		}
	}
	?>
	});
<?php  } ?>

<?php 
if (!empty($this->cartCols['name'])) echo " window.cart_col_name ='{$this->cartCols['name']}';\n";
if (!empty($this->cartCols['price'])) echo " window.cart_col_price ='{$this->cartCols['price']}';\n";
if (!empty($this->cartCols['count'])) echo " window.cart_col_count ='{$this->cartCols['count']}';\n";
if (!empty($this->cartCols['sum'])) echo " window.cart_col_sum ='{$this->cartCols['sum']}';\n";
if (!empty($this->cartCols['type'])) echo " window.cart_col_type ='{$this->cartCols['type']}';\n";

?>
jQuery(function()
{
	jQuery('.cform').prepend("<input type='hidden' name='payment' value='<?php if(isset($_GET['payment'])){ echo $_GET['payment'];}?>'/><input type='hidden' name='wpshop' value='1'/>");
	jQuery('.cform').prepend("<input type='hidden' class='file_name' name='file_name' value=''/>");
	jQuery('.cform .cf_upload').attr('multiple',true);
	
  jQuery('.sendbutton').click(function(){
		var filenames = [];
		jQuery.each(jQuery('.cf_upload').prop("files"), function(k,v){
			var filename = v['name'];
			filenames.push(filename);
		});
		var all_files = JSON.stringify(filenames);
		jQuery('.cform .file_name').val(all_files);
	}); 
});
</script>



<div id="<?php  echo CART_ID;?>">
	<noscript><?php  echo __('You need activate support of JavaScript and Cookies in your browser.', 'wp-shop');?></noscript>
</div>

<?php 
//Подсчет количества общей суммы

$total = 0;
global $wpdb;
$param_sum = array(session_id());
$rows = $wpdb->get_results($wpdb->prepare("SELECT sum(selected_items_cost*selected_items_num) as total FROM {$wpdb->prefix}wpshop_selected_items WHERE selected_items_session_id='%s'",$param_sum));
foreach ($rows as $row) {
	$total = $row->total;
}

$yandex_num = get_option("wpshop.yandex_metrika");
if($yandex_num){
global $wpdb;
$param_yan = array(session_id());
$rows1 = $wpdb->get_results($wpdb->prepare("SELECT selected_items_item_id as id, selected_items_name as name, selected_items_cost as price, selected_items_num as quantity FROM {$wpdb->prefix}wpshop_selected_items WHERE selected_items_session_id='%s'",$param_yan));

$yandex_params= array(
	'order_id'=>time(),
	'order_price'=>$total,
	'goods'=>$rows1
);

$yandex = json_encode($yandex_params,JSON_NUMERIC_CHECK);

?>
<script type="text/javascript">
jQuery(function() {
	jQuery('.cform').submit(function(e){
	  yaCounter<?php echo $yandex_num;?>.reachGoal('wpshop_order_full',<?php echo $yandex;?>);
	});
});
</script>
<?php }

$can_do = true;
if (!empty($this->minzakaz))
{
	if ($total > 0 && $total < $this->minzakaz)
	{
		$can_do	= false;
	}
}

//Определение скидки.
$max_discount = 0;
if ($this->discount != '')
{
	foreach(explode("\r\n",$this->discount) as $value)
	{
		$q = explode(":",$value);
		if ($total > $q[0])
		{
			$max_discount = $q[1];
		}
	}
	echo "<script type='text/javascript'>jQuery(document).ready(function(){
			window.__cart.discount = '" . str_replace("\r","",str_replace("\n",';',$this->discount)) . "';
			window.__cart.update();
	});</script>";
}

global $wpdb;
$param_arr = array(session_id());
$uds = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpshop_selected_items WHERE selected_items_session_id='%s' AND selected_items_uds <> 0",$param_arr));

$uds_result = false;
if($uds!=null):
  $uds_result = true;?>
  <script type="text/javascript">
    jQuery(document).on('cart_load_trigger', 'body', function() {
      jQuery('.uds_code_block_main').hide();
      jQuery('.uds_success_message').show();
    });
  </script>  
<?php endif;
  
echo "<div class='uds_success_message' style='display:none; color: green; font-weight: bold;'>".__('You have successfully used UDS code','wp-shop')."</div>";

if ($total > 0||$uds_result===true) {

	if ($can_do)
	{
		if (function_exists("insert_cform") && ($this->cform !== false || $this->cform != ""))
		{
			if (count($this->payments))
			{
				if (is_user_logged_in()){
					//nothing
				}
				else{
					$just_registred_user_id	= 0;
					$wpshop_reg_error = '';
					$wpshop_auth_error = '';

					if (isset($_POST['wpshop_regiser_usr_btn'])){
						$wpshop_reg_mode = 1;
						$wpshop_style_reg = '';
						$wpshop_style_auth = 'style="display:none"';
					}else{
						$wpshop_reg_mode = 0;
						$wpshop_style_reg = 'style="display:none"';
						$wpshop_style_auth = '';
					}

					if (isset($_POST) and count($_POST)>0){
						if (isset($_POST['wpshop_regiser_usr_btn'])){
							// Register:
							$wpshop_user_name = htmlspecialchars(stripslashes($_POST['wpshop_user_name']));
							$wpshop_user_password = htmlspecialchars(stripslashes($_POST['wpshop_user_password']));
							$wpshop_user_email = htmlspecialchars(stripslashes($_POST['wpshop_user_email']));
							if (strlen($wpshop_user_name)<3 or strlen($wpshop_user_name)>16){
								$wpshop_reg_error .= __('Lenght of the login needs to be 3 to 16 characters.','wp-shop').'<br>';
							}
							if (strlen($wpshop_user_password)<3 or strlen($wpshop_user_password)>16){
								$wpshop_reg_error .= __('Lenght of the password needs to be 3 to 16 characters.','wp-shop').'<br>';
							}
							if (!preg_match("/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/", $wpshop_user_email)){
								$wpshop_reg_error .= __('Incorrect E-mail.','wp-shop').'<br>';
							}

							if (empty($wpshop_reg_error)){
								$user = array(
								    'user_login' => $wpshop_user_name,
								    'user_pass' => $wpshop_user_password,
								    'first_name' => '',
								    'last_name' => '',
								    'user_email' => $wpshop_user_email,
								    'role'=>'Customer'
								    );
								$just_registred_error = wp_insert_user($user);
								if (!is_wp_error($just_registred_error)){
									$just_registred_user_id = $just_registred_error;
									wp_new_user_notification( $just_registred_user_id);
									$wpshop_style_reg = 'style="display:none"';
									$wpshop_style_auth = '';
								}else{
									$wpshop_reg_error = $just_registred_error->get_error_message();
								}
							}
						}elseif($_POST['wpshop_auth_usr_btn']){
							$wpshop_user_name = htmlspecialchars(stripslashes($_POST['wpshop_user_name']));
							$wpshop_user_password = htmlspecialchars(stripslashes($_POST['wpshop_user_password']));
							$creds = array();
							$creds['user_login'] = $wpshop_user_name;
							$creds['user_password'] = $wpshop_user_password;
							$creds['remember'] = false;
							$user = wp_authenticate($wpshop_user_name, $wpshop_user_password);
							if ( is_wp_error($user) ){
								$wpshop_reg_error = $user->get_error_message();
							}
						}

					}else{
						$wpshop_user_name = '';
						$wpshop_user_password = '';
						$wpshop_user_email = '';
						$wpshop_reg_error = '';
					}

					if ($_GET['step']=='2'){
					?>
                <?php $hide_auth = get_option("wpshop.hide_auth");?>
                <?php if($hide_auth !='register'){ ?>
								<div class="wpshop-auth-site">
									<script type="text/javascript">
										function wpshop_reg_form(){
												jQuery('#wpshop-butt-1').hide();
												jQuery('#wpshop-butt-2').show();
												jQuery('#wpshop_txt_auth').hide();
												jQuery('#wpshop_txt_reg').show();
												jQuery('#wpshop-reg_email-txt').show();
												jQuery('#wpshop-reg_email-input').show();
										}
										function wpshop_auth_form(){
												jQuery('#wpshop-butt-1').show();
												jQuery('#wpshop-butt-2').hide();
												jQuery('#wpshop_txt_auth').show();
												jQuery('#wpshop_txt_reg').hide();
												jQuery('#wpshop-reg_email-txt').hide();
												jQuery('#wpshop-reg_email-input').hide();
										}
									</script>

									<div class="wpshop-auth-txt" id="wpshop_txt_auth" <?php  echo $wpshop_style_auth ?>><?php  _e('Checkout as a registered user:', 'wp-shop'); ?></div>
									<div class="wpshop-auth-txt" id="wpshop_txt_reg" <?php  echo $wpshop_style_reg ?>><?php  _e('Registration on the site:', 'wp-shop'); ?></div>
									<br>
									<?php 
										if (!empty($wpshop_reg_error)){
											echo '<div style="font-weight:bold; color:#b00">'.$wpshop_reg_error.'</div><br>';
										}elseif(!empty($wpshop_auth_error)){

											$wpshop_auth_error = str_replace('<a ', '<a target="_blank" ', $wpshop_auth_error);
											echo '<div style="font-weight:bold; color:#b00">'.$wpshop_auth_error.'</div><br>';
										}elseif($just_registred_user_id){
											echo '<div style="font-weight:bold; color:#b00">'.__('You have been successfull registered!','wp-shop').'</div><br>';
										}
										//$current_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
										//echo wp_login_url($current_url);
									?>
									<form action="" method="post" id="wpshop_reg_user_form">
										<div>
											<label for="wpshop_user_name"><?php  _e('Login:', 'wp-shop'); ?></label>
										</div>
										<div>
											<input class="wpshop-name" type="text" name="wpshop_user_name" id="wpshop_user_name" value="<?php  echo $wpshop_user_name; ?>">
										</div>
										<div>
											<label for="wpshop_user_password"><?php  _e('Password', 'wp-shop'); ?>:</label>
										</div>
										<div>
											<input class="wpshop-password" type="password" name="wpshop_user_password" id="wpshop_user_password" value="<?php  echo $wpshop_user_password; ?>">
										</div>
										<div id="wpshop-reg_email-txt" <?php  echo $wpshop_style_reg ?>>
											<label for="wpshop_user_email">E-mail:</label>
										</div>
										<div id="wpshop-reg_email-input" <?php  echo $wpshop_style_reg ?>>
											<input class="wpshop-email" type="text" name="wpshop_user_email" id="wpshop_user_email" value="<?php  echo $wpshop_user_email; ?>">
										</div>
										<div id="wpshop-butt-1" <?php  echo $wpshop_style_auth ?>>
											<input class="wpshop-button1 wpshop-button-bg1" type="submit" name="wpshop_auth_usr_btn" value="<?php  _e('Enter', 'wp-shop'); ?>">
											<input class="wpshop-button2 wpshop-button-bg2" type="button" name="wpshop_regiser_usr_btn" value="<?php  _e('Register', 'wp-shop'); ?>" onclick="wpshop_reg_form()">
										</div>
										<div id="wpshop-butt-2" <?php  echo $wpshop_style_reg ?>>
											<input class="wpshop-button2 wpshop-button-bg1" type="submit" name="wpshop_regiser_usr_btn" value="<?php  _e('Register', 'wp-shop'); ?>">
											<input class="wpshop-button1 wpshop-button-bg2" type="button" name="wpshop_auth_usr_btn" value="<?php  _e('Enter', 'wp-shop'); ?>" onclick="wpshop_auth_form()">
										</div>
									</form>
								</div>
                <?php } ?>
                <?php if($hide_auth !='guest'){ ?>
								<div class="wpshop-auth-site">
									<div class="wpshop-auth-txt"><?php  _e('Or checkout as a guest', 'wp-shop'); ?></div><br>
									
									<form action="" method="get" id="wpshop_reg_user_form">
										<?php if($_GET['page_id']){?>
                      <input type="hidden" name="page_id" value="<?php echo $_GET['page_id'];?>">
                    <?php }?>
										<input type="hidden" name="step" value="3">
										<input type="submit" value="<?php  _e('Checkout as a guest', 'wp-shop'); ?>">
									</form>
									
								</div>
                <?php } ?>
 							
						<?php 
					}

                }
				if (isset($_GET['step']) and $_GET['step']=='2' && is_user_logged_in()) {
					echo "<script type='text/javascript'>document.location='/?step=3'</script>";
				}
				if (isset($_GET['step']) and $_GET['step']=='3'){
					//настройки и проверка что не админ
					//сохранить гет модерейт если есть
						$wpshop_moderate = get_option("wp-shop_moderate");
						if (!isset($_GET['payment']))	{
							if($_GET['moderate']=='true'||$wpshop_moderate!=1){
						?>
							<div id='payments-table'>
								<h3 id='mode-paymets-title'>
									<?php  echo __('Select a payment method', 'wp-shop');?>:
								</h3>
								<ul>
									<?php 
									$robokassa = false;
									$wpshop_merchant_system = get_option("wpshop_merchant_system");
									$wpshop_merchant = get_option("wpshop_merchant");
									foreach($this->payments as $payment)
									{
										if ($payment->data['activate'] == true && $payment->merchant == false )
										{
											echo "<li>
													<a href='{$payment->data[cart_url]}&step=3&payment={$payment->paymentID}'><img src='".WPSHOP_URL."/images/payments/{$payment->picture}' title='{$payment->name}'/></a><br/>
													<a href='{$payment->data[cart_url]}&step=3&payment={$payment->paymentID}'>{$payment->name}</a>
													</li>";
											
										}
									}
									
									foreach($this->payments as $payment)
									{
										if ($payment->merchant == true&&$wpshop_merchant)
										{
											if ($payment->paymentID == "robokassa"&& $wpshop_merchant_system =="robokassa")
											{ 
												$robokassa = $payment;
											}
											if ($payment->paymentID == "ek"&& $wpshop_merchant_system =="ek")
											{ 
												$ek = $payment;
											}
											if ($payment->paymentID == "yandex_kassa"&& $wpshop_merchant_system =="yandex_kassa")
											{ 
												$yandex_kassa = $payment;
											}
										}
									}
									
									?>
								</ul>
							</div>
							<?php  if ($yandex_kassa){ ?>
								<div id='payments-table'>
								<h3 id='mode-paymets-title'>
									<?php 
										echo __('Payment is made through a payment service Yandex kassa <br/> Small extra comission.', 'wp-shop'); //Оплата производится через платежный сервис RoboKassa.ru<br/> Взимается небольшая дополнительная комисcия.
									?>
								</h3>
								<ul>
									<li>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=PC";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/YandexMoneyRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=PC";?>'><?php  echo __('Yandex - Money', 'wp-shop');?></a>
									</li>
									<li>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=AC";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/credit-card.png' title=''/></a><br/>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=AC";?>'><?php  echo __('Credit card', 'wp-shop');?></a>
									</li>
									<li>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=GP";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/CashTerminalRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=GP";?>'><?php  echo __('Terminals', 'wp-shop');?></a>
									</li>
									<li>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=MC";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/MtsRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=MC";?>'><?php  echo __('Mobile', 'wp-shop');?></a>
									</li>
									<?php if( $yandex_kassa->data['webmoney']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=WM";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/webmoney.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=WM";?>'><?php  echo __('Webmoney', 'wp-shop');?></a>
												</li>
									<?php } ?>
									<?php if( $yandex_kassa->data['sber']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=SB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/SberOnlineRUB.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=SB";?>'><?php  echo __('Sberbank online', 'wp-shop');?></a>
												</li>
									<?php } ?>
									<?php if( $yandex_kassa->data['qiwi']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=QW";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/QiwiWalletRUB.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=QW";?>'><?php  echo __('QIWI wallet', 'wp-shop');?></a>
												</li>
									<?php } ?>
									<?php if( $yandex_kassa->data['prom']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=PB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/PsbRetailRUB.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=PB";?>'><?php  echo __('Promsvyazbank', 'wp-shop');?></a>
												</li>
									<?php } ?>
									<?php if( $yandex_kassa->data['master']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=MA";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/masterpass.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=MA";?>'><?php  echo __('MasterPass', 'wp-shop');?></a>
												</li>
									<?php } ?>
									<?php if( $yandex_kassa->data['alfa']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=AB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/AlfaclickRUB.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=AB";?>'><?php  echo __('Alfa-Click', 'wp-shop');?></a>
												</li>
									<?php } ?>
									<?php if( $yandex_kassa->data['credit']){?>
												<li>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=KV";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/KVK_Logo.png' title=''/></a><br/>
													<a href='<?php  echo "{$yandex_kassa->data[cart_url]}&step=3&payment={$yandex_kassa->paymentID}&paymentType=KV";?>'><?php  echo __('Buy in credit', 'wp-shop');?></a>
												</li>
									<?php } ?>
								</ul>
								</div>
							<?php } ?>
							<?php  if ($robokassa){ ?>
								<div id='payments-table'>
								<h3 id='mode-paymets-title'>
									<?php 
										echo __('Payment is made through a payment service RoboKassa.ru <br/> Small extra comission.', 'wp-shop'); //Оплата производится через платежный сервис RoboKassa.ru<br/> Взимается небольшая дополнительная комисcия.
									?>
								</h3>
								<ul>
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=yandex";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/YandexMoneyRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=yandex";?>'><?php  echo __('Yandex - Money', 'wp-shop');?></a>
									</li>
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=card";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/visa.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=card";?>'>Visa</a>
									</li>
									
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=qiwi";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/QiwiWalletRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=qiwi";?>'><?php  echo __('Terminals QIWI', 'wp-shop'); // Терминалы QIWI
										?></a>
									</li>
									
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=wbmoney";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/webmoney.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=wbmoney";?>'>Web money</a>
									</li>
									
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=cashterminal";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/CashTerminalRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=cashterminal";?>'><?php  echo __('Terminals', 'wp-shop'); // Терминалы?></a>
									</li>
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=alfaclick";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/AlfaclickRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=alfaclick";?>'><?php  echo __('Alfa Click', 'wp-shop'); ?></a>
									</li>
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=mts";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/MtsRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=mts";?>'><?php  echo __('MTS', 'wp-shop'); ?></a>
									</li>
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=beeline";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/BeelineRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=beeline";?>'><?php  echo __('beeline', 'wp-shop'); ?></a>
									</li>
									<li>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=mastercard";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/mastercard.png' title=''/></a><br/>
										<a href='<?php  echo "{$robokassa->data[cart_url]}&step=3&payment={$robokassa->paymentID}&rk=mastercard";?>'><?php  echo __('mastercard', 'wp-shop'); ?></a>
									</li>
								</ul>
								</div>
							<?php } ?>
							<?php  if ($ek){?>
								<div id='payments-table'>
								<h3 id='mode-paymets-title'>
									<?php  echo __('Payment is made through a payment service EK <br/> Small extra comission.', 'wp-shop');?>
								</h3>
								<ul>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=VISA";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/visa.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=VISA";?>'><?php  echo __('visa', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=MasterCard";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/mastercard.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=MasterCard";?>'><?php  echo __('mastercard', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/SberOnlineRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}";?>'><?php  echo __('Sberbank-online', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Tinkoff";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/TinkoffRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Tinkoff";?>'><?php  echo __('Tinkoff', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Rsb";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/RsbRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Rsb";?>'><?php  echo __('Russian Standard Bank', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=PSB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/PsbRetailRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=PSB";?>'><?php  echo __('Promsvyazbank', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Alfa";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/AlfaclickRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Alfa";?>'><?php  echo __('Alfa-bank', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Privat";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/Privat24UAH.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Privat";?>'><?php  echo __('Privat bank', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Post";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/RussianPostRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Post";?>'><?php  echo __('Post', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Leader";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/LiderRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Leader";?>'><?php  echo __('Leader', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=WalletOne";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/WalletOneRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=WalletOne";?>'><?php  echo __('WalletOne', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=ukrsib";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/UkrsibBankUAH.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=ukrsib";?>'><?php  echo __('Ukrsib Bank', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=terminalsrub";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/CashTerminalRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=terminalsrub";?>'><?php  echo __('Cash Terminal', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=ContactRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/ContactRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=ContactRUB";?>'><?php  echo __('Contact', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=CifrogradRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/CifrogradRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=CifrogradRUB";?>'><?php  echo __('Cifrograd', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=EurosetRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/EurosetRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=EurosetRUB";?>'><?php  echo __('Euroset', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=SvyaznoyRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/SvyaznoyRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=SvyaznoyRUB";?>'><?php  echo __('Svyaznoy', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Unistream";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/UnistreamRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=Unistream";?>'><?php  echo __('Unistream', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=CellularWorldRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/CellularWorldRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=CellularWorldRUB";?>'><?php  echo __('CellularWorld', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=FakturaruRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/FakturaruRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=FakturaruRUB";?>'><?php  echo __('Faktura.ru', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=OkpayUSD";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/OkpayUSD.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=OkpayUSD";?>'><?php  echo __('Okpay', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=AtmRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/AtmRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=AtmRUB";?>'><?php  echo __('Atm', 'wp-shop'); 
										?></a>
									</li>
									<li>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=BankTransferRUB";?>'><img src='<?php  echo WPSHOP_URL;?>/images/payments/BankTransferRUB.png' title=''/></a><br/>
										<a href='<?php  echo "{$ek->data[cart_url]}&step=3&payment={$ek->paymentID}&rk=BankTransferRUB";?>'><?php  echo __('Bank Transfer', 'wp-shop'); 
										?></a>
									</li>
								</ul>
								</div>
							<?php 
							}
						} else {
							echo '<p>'.__('Order for moderation!', 'wp-shop').'</p>';
						}
					} else {					
						$this->render("RecycleBinPayment.php");
					}					
				}

			}
			elseif ($_GET['step'] == 3 || ($_GET['step'] == 2 && !isset($_GET['payment'])))
			{			
				insert_cform($this->cform);				
			}
		}
		else
		{
			echo '<div style="color:red">';
			_e('Error: Not installed cforms II.', 'wp-shop'); //Ошибка: Не установлен cforms II.
			echo '</div>';
		}
	}
	else
	{
		echo '<span class="minzakaz_info">'.$this->minzakaz_info.'</span>';
	}
}