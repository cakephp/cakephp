(function () {
    'use strict';
    var slides = document.querySelectorAll('.bloc-graph-item'),
        dots = document.querySelectorAll('.dot'),
        carouselCount = 0,
        scrollInterval,
        interval = 5000;

    dots[0].addEventListener('click', function (e) {
        e = e || window.event;
        e.preventDefault();
        carouselCount -= 100;
        slider(e);
        if (e.type !== 'autoClick') {
            clearInterval(scrollInterval);
            scrollInterval = setInterval(autoScroll, interval);
        }
    });
    dots[1].addEventListener('click', sliderEvent);
    dots[1].addEventListener('autoClick', sliderEvent);

    function sliderEvent(e)
    {
        e = e || window.event;
        e.preventDefault();
        carouselCount += 100;
        slider(e);
        if (e.type !== "autoClick") {
            clearInterval(scrollInterval);
            scrollInterval = setInterval(autoScroll, interval);
        }
    }

    function slider(e)
    {
        switch (carouselCount) {
            case -100:
                carouselCount = 0;
                break;
            case 200:
                carouselCount = 0;
                break;
            default:
                break;
        }
        for (var i = 0; i < slides.length; i += 1) {
            slides[i].setAttribute('style', 'transform:translateX(-' + carouselCount + '%)');
        }
        activeDot();
    }

    function activeDot()
    {
        for (var i = 0; i < dots.length; i++) {
            dots[i].classList.remove('active')
        }
        switch (carouselCount) {
            case 0:
                dots[0].classList.add('active');
                break;
            case 100:
                dots[1].classList.add('active');
                break;
            default:
                break;
        }
    }

    // create new Event to dispatch click for auto scroll
    var autoClick = new Event('autoClick');
    function autoScroll()
    {
        dots[1].dispatchEvent(autoClick);
    }

    // set timing of dispatch click events
    scrollInterval = setInterval(autoScroll, interval);
})();
