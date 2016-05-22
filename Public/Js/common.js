$(document).ready(function(){
	//日期函数
	$('#date_input_min,#date_input_max').calendar({format:"yyyy-MM-dd HH:mm:ss"});
	$('.date_input').calendar({format:"yyyy-MM-dd HH:mm:ss"});
	$('.date_input_no_time').calendar({format:"yyyy-MM-dd"});
});