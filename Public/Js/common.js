/**
 * common functions
 */
function createProduct(){
	var name = $("#newProductName").val();
	if(!name){
		showCreateAlert("产品名不能为空");
		return false;
	}
	var data = {"name":name};
	$.post("/Product/insert", data, function(ret){
		try{
			var ret_json = $.parseJSON(ret);
			var msg = ret_json.info;
			if(ret_json.status==1){
				var info = $.parseJSON(msg);
				if(info.id && info.name){//{"id":6,"name":"adsf","created_by":1,"create_time":null}
//					alert(info.id+' '+info.name+' created');
					location.reload();
					return false;
				}
			}
			
		}catch(e){
			var msg = ret;
		}
		showCreateAlert(msg);
	});
}
function createChannel(){
	var channel_name = $("#newChannelName").val();
	if(!channel_name){
		showCreateAlert("渠道名不能为空");
		return false;
	}
	var product_name = $("#select_product_name").val();
	if(!product_name){
		showCreateAlert("产品名不能为空");
		return false;
	}
	var data = {"channel_name":channel_name, "product_name":product_name};
	$.post("/Channel/insert", data, function(ret){
		try{
			var ret_json = $.parseJSON(ret);
			var msg = ret_json.info;
			if(ret_json.status==1){
				var info = $.parseJSON(msg);
				if(info.id && info.name){
					window.location.href = '/Product/view/pname/'+info.productName;
					return false;
				}
			}
			
		}catch(e){
			var msg = ret;
		}
		showCreateAlert(msg);
	});
}
function createLpitem(){
	var channel_id = $('#channel_selector option:selected').val();
	if(!channel_id){
		showCreateAlert("渠道不能为空");
		return false;
	}
//	var language = $("#language_selector option:selected").val();
//	if(!language){
//		showCreateAlert("语言不能为空");
//		return false;
//	}
	var data = {"channel_id":channel_id, "language":language};
	$.post("/Lpitem/insert", data, function(ret){
		try{
			var ret_json = $.parseJSON(ret);
			var msg = ret_json.info;
			if(ret_json.status==1){
				var info = $.parseJSON(msg);
				if(info.id && info.name){
					window.location.href = '/Product/view/pname/'+info.productName+'/cname'+info.channelName;
					return false;
				}
			}
			
		}catch(e){
			var msg = ret;
		}
		showCreateAlert(msg);
	});
}
function showAlert(alertNodeName, msg){
	$('#'+alertNodeName).html('<div class="alert"><a class="close" data-dismiss="alert">×</a><span>'+msg+'</span></div>')
}
function showSuccessAlert(alertNodeName, msg){
	$('#'+alertNodeName).html('<div class="alert alert-success"><a class="close" data-dismiss="alert">×</a><span>'+msg+'</span></div>')
}
function clearAlert(alertNodeName){
	$('#'+alertNodeName).empty();
}
function showCreateAlert(msg){
	showAlert('create_alert', msg);
}
$(document).ready(function(){
	$("#channel_name_selector").change(function(){
		refreshLpitems();
	});
	$("#supported_languages_selector").change(function(){
		refreshLpitems();
	});
	$("#lpfile_name_selector").change(function(){
		refreshResource();
	});
	$("#resource_supported_languages_selector").change(function(){
		refreshResource();
	});
	$("#lpfile_checkall").click(function(){
		status = $("#lpfile_checkall").val();
		if(status=='none'){
			$(".lpfile_checkbox").attr('checked', true);
			$("#lpfile_checkall").val('all');
			$("#lpfile_checkall").attr('checked', true);
		}else if(status=='all'){
			$(".lpfile_checkbox").removeAttr('checked');
			$("#lpfile_checkall").val('none');
			$("#lpfile_checkall").removeAttr('checked');
		}
	});
	$("#lpitem_checkall").click(function(){
		status = $("#lpitem_checkall").val();
		if(status=='none'){
			$(".lpitem_checkbox").attr('checked', true);
			$("#lpitem_checkall").val('all');
			$("#lpitem_checkall").attr('checked', true);
		}else if(status=='all'){
			$(".lpitem_checkbox").removeAttr('checked');
			$("#lpitem_checkall").val('none');
			$("#lpitem_checkall").removeAttr('checked');
		}
	});
	$("#translate_channel_name_selector").change(function(){
		refreshTranslateList();
	});
	$("#translate_languages_selector").change(function(){
		refreshTranslateList();
	});
	$("#cdn_channel_name_selector").change(function(){
		refreshCdnList();
	});
	$("#cdn_languages_selector").change(function(){
		refreshCdnList();
	});
	$("#stats_product_name_selector").change(function(){
		refreshStats();
	});
	$("#stats_channel_name_selector").change(function(){
		refreshStats();
	});
	$("#stats_languages_selector").change(function(){
		refreshStats();
	});
	var startDate = new Date();
	var endDate = new Date();
	var startDateString = startDate.getFullYear()+'-'+(startDate.getMonth()+1)+'-'+startDate.getDate();
	var endDateString = endDate.getFullYear()+'-'+(endDate.getMonth()+1)+'-'+endDate.getDate();
	$('#date-start').text(startDateString);
	$('#date-start').attr('data-date', startDateString);
	$('#date-end').text(endDateString);
	$('#date-end').attr('data-date', endDateString);
	$('#date-start')
	    .datepicker()
	    .on('changeDate', function(ev){
	        if (ev.date.valueOf() > endDate.valueOf()){
	            showAlert('datepicker_alert', 'The start date must be before the end date.');
	        } else {
	        	clearAlert('datepicker_alert');
	            startDate = new Date(ev.date);
	            $('#date-start').text($('#date-start').data('date'));
	        }
	        $('#date-start').datepicker('hide');
	        var datestr = "custom|"+$('#date-start').data('date')+"|"+$('#date-end').data('date');
	        $("#date_str").val(datestr);
	        refreshStats();
	    });
	$('#date-end')
	    .datepicker()
	    .on('changeDate', function(ev){
	        if (ev.date.valueOf() < startDate.valueOf()){
	        	showAlert('datepicker_alert', 'The start date must be before the end date.');
	        } else {
	        	clearAlert('datepicker_alert');
	            endDate = new Date(ev.date);
	            $('#date-end').text($('#date-end').data('date'));
	        }
	        $('#date-end').datepicker('hide');
	        var datestr = "custom|"+$('#date-start').data('date')+"|"+$('#date-end').data('date');
	        $("#date_str").val(datestr);
	        refreshStats();
	    });
	$(".stats_date_selector").click(function(e){
		clearAlert('datepicker_alert');
		var datestr = this.id.substring(14);
		var datetext = this.innerHTML;
		updateDateDropdown(datetext);
		if(datestr=='custom'){
			$("#datepicker_div").show();
			return true;
		}else{
			$("#datepicker_div").hide();
		}
		$("#date_str").val(datestr);
		refreshStats();
	});
});
function timestampToDate(timestamp) {
    var d = new Date();
    var tz = d.getTimezoneOffset();
    var date = new Date((parseInt(timestamp)+tz*60) * 1000);
    var year = date.getFullYear();
    var month = date.getMonth()+1;
    var day = date.getDate();
    var hour = date.getHours();
    var minute = date.getMinutes();
    var second = date.getSeconds();
    var datestr = year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second;//.replace(/年|月/g, "-").replace(/日/g, " ");
    return datestr;
}
function startTranslate(){
	var pname = $("#select_product_name").val();
	var cname = $("#translate_channel_name_selector").val();
	var language = $("#translate_languages_selector").val();
	var lpfileIds = '';
	$(".lpfile_checkbox:checked").each(function(i){
		lpfileIds += $(this).val()+",";
	});
	lpfileIds = lpfileIds.substring(0, lpfileIds.length-1);
	var data = {"pname":pname, "language":language, "cname":cname, "lpfileIds":lpfileIds};
	$("#translate_button").attr("class", "btn btn-primary disabled");
	$("#translate_button").attr("data-loading-text", "处理中...");
	$("#translate_button").button('loading');
	$("#translate_button").removeAttr("onclick");
	clearAlert("translate_alert");
	$.post("/Lpitem/translate", data, function(ret){
		$("#translate_button").button('reset');
		$("#translate_button").attr("onclick", "startTranslate();");
		var ret_json = $.parseJSON(ret);
		if(ret_json.status && ret_json.info=='ok'){
			refreshTranslateList();
		}else if(!ret_json.status && ret_json.info=='translated'){
			showAlert("translate_alert", '已经翻译过');
		}else if(!ret_json.status && ret_json.info=='noresource'){
			showAlert("translate_alert", '尚未添加资源文件');
		}else{
			showAlert("translate_alert", ret_json.info);
		}
	});
}
function deployCDN(){
	var pname = $("#select_product_name").val();
	var cname = $("#cdn_channel_name_selector").val();
	var language = $("#cdn_languages_selector").val();
	var lpfile_list = '';
	$(".lpfile_checkbox:checked").each(function(i){
		var id = $(this).val();
		lpfile_list += id+",";
	});
	lpfile_list = lpfile_list.substring(0, lpfile_list.length-1);
	var data = {"pname":pname, "cname":cname, "lpfileIds":lpfile_list};
	$("#cdn_button").attr("class", "btn btn-primary disabled");
	$("#cdn_button").attr("data-loading-text", "处理中...");
	$("#cdn_button").button('loading');
	$("#cdn_button").removeAttr("onclick");
	clearAlert("cdn_alert");
	$.post("/Lpitem/deploy", data, function(ret){
		$("#cdn_button").button('reset');
		$("#cdn_button").attr("onclick", "deployCDN();");
		var ret_json = $.parseJSON(ret);
		if(ret_json.status && ret_json.info=='ok'){
			refreshCdnList();
		}else if(!ret_json.status && ret_json.info=='translated'){
			showAlert("cdn_alert", '已经翻译过');
		}else if(!ret_json.status && ret_json.info=='noresource'){
			showAlert("cdn_alert", '尚未添加资源文件');
		}else{
			showAlert("cdn_alert", ret_json.info);
		}
	});
}
function batchUpdateCDN(){
	var pname = $("#select_product_name").val();
	var lpitem_list = '';
	$(".lpitem_checkbox:checked").each(function(i){
		var id = $(this).val();
		lpitem_list += id+",";
	});
	lpitem_list = lpitem_list.substring(0, lpitem_list.length-1);
	var data = {"pname":pname, "lpitemIds":lpitem_list};
	$("#cdn_button").attr("class", "btn btn-primary disabled");
	$("#cdn_button").attr("data-loading-text", "处理中...");
	$("#cdn_button").button('loading');
	$("#cdn_button").removeAttr("onclick");
	clearAlert("cdn_alert");
	$.post("/Lpitem/batchDeploy", data, function(ret){
		$("#cdn_button").button('reset');
		$("#cdn_button").attr("onclick", "batchUpdateCDN();");
		var ret_json = $.parseJSON(ret);
		if(ret_json.status && ret_json.info=='ok'){
			location.reload();
		}else{
			showAlert("cdn_alert", ret_json.info);
		}
	});
}
function refreshResource(){
	var pname = $("#select_product_name").val();
	var lpfile = $("#lpfile_name_selector").val();
	var language = $("#resource_supported_languages_selector").val();
	var data = {"pname":pname, "lpfile":lpfile, "language":language};
	$.post("/Resource/filter", data, function(ret){
		var ret_json = $.parseJSON(ret);
		var resource = ret_json.data;
		$("#resource_list tr:not(:first)").remove();
		if(resource){
			var lpfile_access_url = $("#lpfile_access_url").val();
			for(var i=0;i<resource.length;i++){
				var row = '<tr><td>'+resource[i]['name']+'</td><td>'+resource[i]['language']+'</td><td><a target="href" href="http://'+lpfile_access_url+'_resource/'+resource[i]['lpfile_id']+'/'+resource[i]['language']+'/'+resource[i]['name']+'">'+resource[i]['name']+'</a></td><td>'+resource[i]['version']+'</td><td>'+resource[i]['creator']+'</td><td>'+timestampToDate(resource[i]['update_time'])+'</td></tr>';
				$('#resource_list > tbody:last').append(row);
			}
		}
	});
}
function refreshTranslateList(){
	var pname = $("#select_product_name").val();
	var cname = $("#translate_channel_name_selector").val();
	var language = $("#translate_languages_selector").val();
	var na_cdn_server = $("#na_cdn_server").val();
	var ml_cdn_server = $("#ml_cdn_server").val();
	var kratos_project_id = $("#kratos_project_id").val();
	var data = {"pname":pname, "cname":cname, "language":language};
	var statusText = {1:"未翻译，须添加相应语言的图片资源", 2:"待翻译", 3:"已翻译并提交CDN", 4:"LP语言包，无需翻译", 5:"已翻译,但有资源版本更新", 6:"LP语言包，无需翻译，有版本更新", 7:"无下载包链接"};
	var classType = {1:"error", 2:"warning", 3:"success", 4:"success", 5:"warning", 6:"warning", 7:"error"};
	$.post("/Lpitem/getTranslateList", data, function(ret){
		var ret_json = $.parseJSON(ret);
		var lpfiles = ret_json.data;
		$("#lpfile_list tr:not(:first)").remove();
		if(lpfiles){
			for(var i=0;i<lpfiles.length;i++){
				if(lpfiles[i]['type']==2){
					continue;
				}
				var url = "";
				var checkbox = "";
				var key = "url";
				if(key in lpfiles[i] && lpfiles[i][key]){
					url = '<a target="href" href="http://'+lpfiles[i][key]+'">'+lpfiles[i]['access_name']+'</a>';
				}
				if(lpfiles[i]['status']==2 || lpfiles[i]['status']==3 || lpfiles[i]['status']==5){
					checkbox = '<input type="checkbox" class="lpfile_checkbox" value="'+lpfiles[i]['id']+'">';
				}
				var row = '<tr class="'+classType[lpfiles[i]['status']]+'"><td>'+checkbox+'</td><td>'+lpfiles[i]['name']+'</td><td>'+lpfiles[i]['language']+'</td><td>'+statusText[lpfiles[i]['status']]+'</td><td>'+url+'</td><td>'+lpfiles[i]['version']+'</td><td>'+timestampToDate(lpfiles[i]['update_time'])+'</td></tr>';
				$('#lpfile_list > tbody:last').append(row);
			}
		}
	});
}
function refreshCdnList(){
	var pname = $("#select_product_name").val();
	var cname = $("#cdn_channel_name_selector").val();
	var language = $("#cdn_languages_selector").val();
	var na_cdn_server = $("#na_cdn_server").val();
	var ml_cdn_server = $("#ml_cdn_server").val();
	var kratos_project_id = $("#kratos_project_id").val();
	var data = {"pname":pname, "cname":cname, "language":language};
	var cdnStatusText = {1:"尚未部署到CDN", 2:"已经部署到CDN", 3:"已部署到CDN，但有资源更新"};
	var statusText = {7:"无下载包链接"};
	var classType = {1:"warning", 2:"success", 3:"warning"};
	$.post("/Lpitem/getCdnList", data, function(ret){
		$("#lpfile_list tr:not(:first)").remove();
		var ret_json = $.parseJSON(ret);
		if(ret_json.status==0){
			showAlert('cdn_alert', ret_json.info);
			return false;
		}
		var lpfiles = ret_json.data;
		if(lpfiles){
			var language_html = $("#cdn_languages_selector").html();
			var index = language_html.indexOf("</option>");
			language_html = language_html.substring(index+9);
			for(var i=0;i<lpfiles.length;i++){
				if(lpfiles[i]['type']==1){
					continue;
				}
				if(language && lpfiles[i]['language']!=language){
					continue;
				}
				if(lpfiles[i]['status']==6){
					lpfiles[i]['cdnStatus'] = 3;
				}
				var status = "";
				var checkbox = "";
				var row_class = "";
				if(lpfiles[i]['status']==7){
					status = statusText[lpfiles[i]['status']];
					row_class = "error";
				}else{
					status = cdnStatusText[lpfiles[i]['cdnStatus']];
					checkbox = '<input type="checkbox" class="lpfile_checkbox" value="'+lpfiles[i]['id']+'">';
					row_class = classType[lpfiles[i]['cdnStatus']];
				}
				var url = "";
				var key = "url";
				if(key in lpfiles[i] && lpfiles[i][key]){
					url = '<a target="href" href="http://'+lpfiles[i][key]+'">'+lpfiles[i]['access_name']+'</a>';
				}
				var row = '<tr class="'+row_class+'"><td>'+checkbox+'</td><td>'+lpfiles[i]['name']+'</td><td>'+lpfiles[i]['language']+'</td><td>'+status+'</td><td>'+url+'</td><td>'+lpfiles[i]['version']+'</td><td>'+timestampToDate(lpfiles[i]['update_time'])+'</td></tr>';
				$('#lpfile_list > tbody:last').append(row);
			}
		}
	});
}
function refreshLpitems(){
	var pname = $("#select_product_name").val();
	var cname = $("#channel_name_selector").val();
	var language = $("#supported_languages_selector").val();
	var na_cdn_server = $("#na_cdn_server").val();
	var ml_cdn_server = $("#ml_cdn_server").val();
	var kratos_project_id = $("#kratos_project_id").val();
	var data = {"pname":pname, "cname":cname, "language":language};
	clearAlert("set_weight_alert");
	$('#lp_access_link').empty();
	if(cname && language){
		fileUrl = '/hub/'+pname+'/'+language+'/'+cname;
		$('#lp_access_link').html('访问链接: <a href="'+fileUrl+'" target="_blank">'+fileUrl+'</a>');
	}
	$.post("/Lpitem/filter", data, function(ret){
		var ret_json = $.parseJSON(ret);
		var lpitems = ret_json.data;
		var statusText = {1:"正常",2:"资源更新后尚未翻译"};
		var classType = {1:"success", 2:"warning"};
		$("#lpitem_list tr:not(:first)").remove();
		if(lpitems){
			for(var i=0;i<lpitems.length;i++){
				var status = "";
				if(lpitems[i]['status']){
					status = statusText[lpitems[i]['status']];
				}
				var url_language = lpitems[i]['language'];
				if(lpitems[i]['type']==2){
					url_language = 'en';
				}
				var row = '<tr class="'+classType[lpitems[i]['status']]+'"><td>'+lpitems[i]['channelName']+'</td><td>'+lpitems[i]['language']+'</td><td>'+lpitems[i]['fileKey']+'</td><td><a target="href" href="'+ml_cdn_server+kratos_project_id+'/'+url_language+'/'+lpitems[i]['url']+'?xcv='+lpitems[i]['ml_version']+'">'+lpitems[i]['fileName']+'</a></td><td><a target="href" href="'+na_cdn_server+kratos_project_id+'/'+url_language+'/'+lpitems[i]['url']+'?xcv='+lpitems[i]['ml_version']+'">'+lpitems[i]['fileName']+'</a></td><td><input type="text" style="width:100px;" name="'+lpitems[i]['id']+'"  value="'+lpitems[i]['weight']+'"/></td><td>'+lpitems[i]['local_version']+'</td><td>'+status+'</td><td>'+timestampToDate(lpitems[i]['update_time'])+'</td></tr>';
				$('#lpitem_list > tbody:last').append(row);
			}
		}
	});
}
function setLpitemWeight(){
	var cname = $("#channel_name_selector").val();
	if(!cname){
		showAlert("set_weight_alert", '请选择渠道');
		return false;
	}
//	var language = $("#supported_languages_selector").val();
//	if(!language){
//		showAlert("set_weight_alert", '请选择语言');
//		return false;
//	}
	clearAlert("set_weight_alert");
	var input = $("#lpitem_list").find("input");
	var data = '[';
	for(var i=0;i<input.length;i++){
		data += '{"lpitem_id":"'+input[i]['name']+'","weight":"'+input[i]['value']+'"}';
		if(i!=input.length-1){
			data += ',';
		}
	}
	data += ']';
	$.post("/Lpitem/weight", data, function(ret){
		var ret_json = $.parseJSON(ret);
		var msg = ret_json.info;
		if(msg=='ok'){
			showSuccessAlert("set_weight_alert", '设置成功！');
		}else if(msg=='wrong weight'){
			showAlert("set_weight_alert", '请设置正确比例');
		}else{
			showAlert("set_weight_alert", '设置失败，请与管理员联系');
		}
	});
}
function updateDateDropdown(datestr){
	$("#date_selector_head").html('选择日期: '+datestr+'<span class="caret"></span>');
}
function refreshStats(){
	var pname = $("#stats_product_name_selector").val();
	var cname = $("#stats_channel_name_selector").val();
	var language = $("#stats_languages_selector").val();
	var kratos_project_id = $("#kratos_project_id").val();
	var datestr = $("#date_str").val();
	var na_cdn_server = $("#na_cdn_server").val();
	var data = {"pname":pname, "cname":cname, "language":language, "date":datestr};
	$.post("/Stats/filter", data, function(ret){
		var ret_json = $.parseJSON(ret);
		var stats = ret_json.data;
		$("#lpstats_list tr:not(:first)").remove();
		if(stats){
			for(var i=0;i<stats.length;i++){
				var ctr = parseInt(stats[i]['clk'])*100/parseInt(stats[i]['imp']);
				ctr = ctr.toFixed(2)+'%';
				var loose = 'NA';
				if(parseInt(stats[i]['pv'])>0){
					loose = (parseInt(stats[i]['pv'])-parseInt(stats[i]['imp']))*100/parseInt(stats[i]['pv']);
					if(loose<0){
						loose = 'NA';
					}else{
						loose = loose.toFixed(2)+'%';
					}
				}
				var url_language = stats[i]['language'];
				if(stats[i]['file_type']==2){
					url_language = 'en';
				}
				var row = '<tr><td>'+stats[i]['product']+'</td><td>'+stats[i]['language']+'</td><td>'+stats[i]['channel']+'</td><td>'+stats[i]['fileName']+'</td><td><a target="href" href="'+na_cdn_server+kratos_project_id+'/'+url_language+'/'+stats[i]['url']+'?xcv='+stats[i]['ml_version']+'">'+stats[i]['url']+'</a></td><td>'+stats[i]['pv']+'</td><td>'+stats[i]['imp']+'</td><td>'+stats[i]['clk']+'</td><td>'+loose+'</td><td>'+ctr+'</td></tr>';
				$('#lpstats_list > tbody:last').append(row);
			}
		}
	});
}
function setUser(){
	var uid = $("#userId").val();
	var email = $("#inputEmail").val();
	var data = {"uid":uid, "email":email};
	$.post("/User/set", data, function(ret){
		var ret_json = $.parseJSON(ret);
		if(ret_json.status && ret_json.info=='ok'){
			showSuccessAlert("user_alert", '设置成功');
		}else{
			showAlert("user_alert", '出错了');
		}
	});
}
function changePassword(){
	var uid = $("#userId").val();
	var password = $("#old_assword").val();
	var new_password = $("#new_password").val();
	var new_password_confirm = $("#new_password_confirm").val();
	if(new_password!==new_password_confirm){
		showAlert("user_alert", '新密码不匹配');
		return false;
	}
	var data = {"uid":uid, "password":password, "new_password":new_password};
	$.post("/User/setPw", data, function(ret){
		var ret_json = $.parseJSON(ret);
		if(ret_json.status && ret_json.info=='ok'){
			showSuccessAlert("user_alert", '修改成功');
		}else if(ret_json.info=='wrongpw'){
			showAlert("user_alert", '原密码错误');
		}else{
			showAlert("user_alert", '出错了');
		}
	});
}