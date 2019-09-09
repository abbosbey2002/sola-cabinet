$( () => {
	
	//On Scroll Functionality
	$(window).scroll( () => {
		var windowTop = $(window).scrollTop();
		windowTop > 20 ? $('nav').addClass('navShadow') : $('nav').removeClass('navShadow');
	});

	$(window).scroll( () => {
		var windowTop = $(window).scrollTop();
		windowTop > 20 ? $('.link').addClass('black') : $('.link').removeClass('black');
	});

	var swiper = new Swiper('.swiper-container', {

		slidesPerView: 1.5,
		spaceBetween: 30
	});


	
	//Click Logo To Scroll To Top
	$('#logo').on('click', () => {
		$('html,body').animate({
			scrollTop: 0
		},500);
	});
	
	//Smooth Scrolling Using Navigation Menu
	$('a.anim').on('click', function(e){
		$('html,body').animate({
			scrollTop: $($(this).attr('.anim')).offset().top - 100
		},500);
		e.preventDefault();
	});

	
});




var config = {
	elementID: 'touchSideSwipe',
            elementWidth: 300, //px
            elementMaxWidth: 0.8, // *100%
            sideHookWidth: 44, //px
            moveSpeed: 0.5, //sec
            opacityBackground: 0.5,
            shiftForStart: 50, // px
            windowMaxWidth: 1024, // px
        }
        var touchSideSwipe = new TouchSideSwipe(config);


