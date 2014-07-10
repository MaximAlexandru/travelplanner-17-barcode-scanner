function menu_init(){
	// localStorage.removeItem('navbarPanel')
	if(localStorage['navbarPanel']){
		return localStorage['navbarPanel'];
	}
	else
	{
	
          var menu;        
			$what="header";
			string="header_request="+$what;		
		    $.ajax({
            type: 'POST',
            url: "http://www.brasovtour.com/mobile-app/ajax/ajax.php",
            data:  string,
            success:function(response){
            	menu=response;
            	localStorage['navbarPanel']=response;
            	
          		}
		  });
		  return menu;
		  
	 }
		
		 
								
}
function navbar_init(){
	var nav ='<a href="#" class="ui-btn-left button" id="btn-menu">Menu</a>';
	return nav;
	
}
function navbar(d){
	 	var header = $(d).find('#wrap-header');
	 	header.empty();
	 	header.append(navbar_init());
	    header.find('#btn-menu').on('click',function(){
		 $('#menu').panel('open');
 		 });	
}


