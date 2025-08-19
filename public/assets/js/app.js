document.addEventListener('click',function(e){
	var t=e.target;
	if(t.matches('[data-confirm]')){
		if(!confirm(t.getAttribute('data-confirm'))){e.preventDefault();}
	}
});