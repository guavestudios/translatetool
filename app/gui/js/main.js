$(function(){
	
});

function startPoll(){
	$.ajax({
		type: "POST",
		data: lastUpdated,
		url: "poll",
		dataType: "json",

		async: true, /* If set to non-async, browser shows page as "Loading.."*/
		cache: false,
		timeout:30000, /* Timeout in ms */

		success: function(data){
			if(data.status == true){
				if(typeof(data.response) == 'object'){
					for(i in data.response.tables){
						
					}
				}
				startPoll();
			}else{
				console.error('Error in longpolling service', data);
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown){
			console.error('There was an error while polling the server... restarting service');
			setTimeout(2000, startPoll);
		}
	});
}