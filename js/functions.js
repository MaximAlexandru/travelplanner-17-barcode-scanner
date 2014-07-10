var contact;
var location_id=0;
var user_id=0;
function check_qr(text){
	location_id = localStorage['user_id'];
	string="check_qr="+text+"&"+"node_id="+location_id;
	$.blockUI({ message: '<img src="http://brasovtour.com/mobile-app/img/busy.gif" />' });
        $.ajax({
            type: 'POST',
            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
   		   
            data:  string,
            success:function(response){
           alert(response);
           $.unblockUI();
            
            	
		  }
		});
	
	
}
function sendreservation(){	
	string=$('#reservationForm').serialize();
	$.blockUI({ message: '<img src="http://brasovtour.com/mobile-app/img/busy.gif" />' });
        $.ajax({
            type: 'POST',
            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
   		    dataType: "json",
            data:  string,
            success:function(response){
           		$.unblockUI();
		  }
		});
}
function populate_loc(){
	
	$('.location-contact').empty();
	$('.location-contact').html(contact);
	$('.location-contact').trigger('create');
	$('#websitepopup').popup();
	
}
function rezervare(){ 

	user= localStorage['user_id'];
	locationid = location_id;
	
	string="what=get_reservation_data"+"&"+"rez_user_id="+user+"&"+"node_id="+locationid+"&"
	+"mobile_app=true";
	
        $.ajax({
            type: 'POST',
            url: "http://www.brasovtour.com/libs/ajax/ajax_functions.php",
   	
            data:  string,
            success:function(response){
   	$('#location').find('#rezervare_response_append').empty().append(response);
       }
		});
	
	
	
}
function populate_categorie(id,id2){//id-supercategoria,id2-categoria
	
	string="category_id="+id2;
	$.blockUI({ message: '<img src="http://brasovtour.com/mobile-app/img/busy.gif" />' });
        $.ajax({
            type: 'POST',
            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
   		    dataType: "json",
            data:  string,
            success:function(response){
               	$('.category-name').empty().append(response[0]);
               	$('.category-locations').empty().append(response[1]);
               	
            	
            	$.unblockUI();
            
            	
		  }
		});
}
function show_cut_text(){
	
	$('.cut-text').show();
	$('.more_text').hide();
}
function getfooterlocation(){
	footer='<a href="#map" class="ui-btn-left button" id="btn-menu">Directions</a>'+
'<a  data-toggle="modal" data-target="#reservationModal" onclick="rezervare()" data-rel="popup"  data-position-to="window" class="ui-btn-right button"'+
				'id="btn-menu">Rezervare</a>'+
				'<div class="footer-rezervation"></div>';
	$('#location').find('#footer-wrapper').empty().append(footer).trigger('create');
	$('#rezervarepopup').popup();
	
	
	
}
function user_qr_code(){
	string="qr_code_id="+localStorage['user_id'];
	$.blockUI({ message: '<img src="http://brasovtour.com/mobile-app/img/busy.gif" />' });
        $.ajax({
            type: 'POST',
            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
            data:  string,
            success:function(response){
                $('.qr_code_img').empty().append(response).trigger('create');           	
            	$.unblockUI();
            
            	
		  }
		});
	
}


function validate_login(){
		 var username = $('#username').val();
		 var password = $('#password2').val();
		
		string=$("#form-login").serialize();
		$.blockUI({ message: '<img src="http://www.brasovtour.com/mobile-app/img/busy.gif" />' });
        $.ajax({
	            type: 'POST',
	            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
	   		    dataType: "json",
	            data:  string,
	            success:function(response){
	            	console.log(response);
	            	if(response['succes'])
	            	{
	            		
			              localStorage['name']=username;
						 
						  localStorage['user_id']=response['succes'];
						  localStorage['user_type']=response['type'];
						
						  if(response['type']=='user')
	            		{
						  window.location="#acasa";
						 }
						 else if(response['type']=='location-user')
						 {
						 	window.location="index-locatie.html";
						 }
              
            		 }
            		 else
             		{
             			alert('Eroare:'+response['error']);
             		}
             $.unblockUI();
	            	}
			});
		}

function setLocationId(id){
	location_id=id;
	$('#location').find('#node_id').val(id);
	
}
	function login_history(){
		
		// localStorage.removeItem('name');
	    // localStorage.removeItem('password');
	    // localStorage.removeItem('user_id');
	    // localStorage.removeItem('user_type');
	    // alert('user:'+localStorage.removeItem('name'));
	 
		if(localStorage['name'] && localStorage['user_id'] && localStorage['user_type'])
		{
			if(localStorage['user_type']=='user')
			{
				window.location="#acasa";
				
			}
			else
			{
				window.location='index-locatie.html';
			}
		}
		else
		{
			
	
			window.location="#login";
 		}
	}
	
	
	function populate_location(id){
		
		
		$('.location-photos').empty();
		$('.location-contact').empty();
		$('.location-name').empty();
		$('.location-facilities').empty();
		$('.location-description').empty();
		$('.location-program').empty();

		          
		string="location_id="+id;
		$.blockUI({ message: '<img src="http://www.brasovtour.com/mobile-app/img/busy.gif" />' });
        $.ajax({
		            type: 'POST',
		            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
		   		    dataType: "json",
		            data:  string,
		            success:function(response)
		            {
		            	dlat=response['lat'];
		            	dlng=response['lng'];
		            	$('.location-name').empty().append(response['name']);
		            	$('.location-facilities').empty().append(response['facilities']);
		            	$('.location-description').empty().append(response['description']);
			            	if(response['program-table'])
			            	{
			            		$('.location-program').empty().append(response['program-table']);
			                }
		                $('.location-photos').empty().append(response['photos']);
		                $('.location-photos').append(response['photos_pop']);
		                $('.location-photos').trigger('create');
		                // $('.location-contact').empty().append(response['contact']);
		               
		            
		              contact=response['contact'];
		              populate_loc();
		                $.unblockUI();
	                  
		        	  }
		});
		}
