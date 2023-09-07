jQuery(document).ready(function ($) {
    $.the7Slider = function (el) {
        const data = {
            selectors: {
                slider: '.elementor-slides-wrapper',
                slide: '.swiper-slide',
                slideInnerContents: '.the7-slide-content',
                activeSlide: '.swiper-slide-active',
                activeDuplicate: '.swiper-slide-duplicate-active'
            },
            classes: {
                animated: "animated",
                elementorInvisible: "elementor-invisible",
                the7Hidden: "the7-slide-hidden",
            },
            attributes: {
                dataAnimation: 'animation'
            },
            changeableProperties: {
                pause_on_hover: 'pauseOnHover',
                autoplay_speed: 'delay',
                transition_speed: 'speed',
                autoplay: 'autoplay'
            }
        };

        let $widget = $(el),
            elementorSettings,
            settings,
            methods,
            swiper,
            intersectionObserver,
            elements = {
                $swiperContainer: $widget.find(data.selectors.slider),
                animatedSlides: {}
            };

        elements.$slides = elements.$swiperContainer.find(data.selectors.slide);
        $widget.vars = {
            sliderInitialized: false
        };
        // Store a reference to the object
        $.data(el, "the7Slider", $widget);
        // Private methods
        methods = {
            init: function () {
                elementorSettings = new The7ElementorSettings($widget);
                $widget.refresh();
                this.initSlider();
                methods.handleResize = elementorFrontend.debounce(methods.handleResize, 1000);
            },
            bindEvents: function () {
                methods.initIntersectionObserver();
                elementorFrontend.elements.$window.on('resize', methods.handleResize);
            },
            unBindEvents: function () {
                elementorFrontend.elements.$window.off('resize', methods.handleResize);
                if (intersectionObserver !== undefined) {
                    intersectionObserver.unobserve($widget[0]);
                    intersectionObserver = undefined;
                }
            },
            handleResize: function () {
                methods.removeElementsAnimation();
                methods.findAnimationInElements();
                methods.removeElementsAnimation();
                methods.addElementsAnimation();
            },
            getSlidesCount: function () {
                return elements.$slides.length;
            },
            initIntersectionObserver: function () {
                if ('yes' !== settings.autoplay) return;
                intersectionObserver = elementorModules.utils.Scroll.scrollObserver({
                    offset: '-15% 0% -15%',
                    callback: event => {
                        if (!$widget.vars.sliderInitialized) return;
                        if (event.isInViewport) {
                            swiper.autoplay.start();
                        } else {
                            swiper.autoplay.stop();
                        }
                    }
                });
                intersectionObserver.observe($widget[0]);
            },
            getSwiperOptions: function () {
                swiperOptions = {
                    autoplay: this.getAutoplayConfig(),
                    grabCursor: true,
                    initialSlide: this.getInitialSlide(),
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    loop: 'yes' === settings.infinite,
                    pauseOnMouseEnter: true,
                    speed: settings.transition_speed,
                    effect: settings.transition,
                    observeParents: true,
                    observer: true,
                    handleElementorBreakpoints: true,
                };
                swiperOptions.autoHeight = true;
                const showArrows = true,
                    pagination = true;
                if (showArrows) {
                    swiperOptions.navigation = {
                        prevEl: '.the7-swiper-button-prev',
                        nextEl: '.the7-swiper-button-next'
                    };
                }
                if (pagination) {
                    swiperOptions.pagination = {
                        el: '.swiper-pagination',
                        type: 'bullets',
                        bulletActiveClass: 'active',
                        bulletClass: 'owl-dot',
                        clickable: true,
                        renderBullet: function (index, className) {
                            return '<button role="button" class="' + className + '" aria-label="Go to slide ' + index + 1 + '"><span></span></button>';
                        },
                    };
                }
                if (true === swiperOptions.loop) {
                    swiperOptions.loopedSlides = this.getSlidesCount();
                }
                if ('fade' === swiperOptions.effect) {
                    swiperOptions.fadeEffect = {
                        crossFade: true
                    };
                }
                return swiperOptions;
            },
            getAutoplayConfig: function () {
                if ('yes' !== settings.autoplay) {
                    return false;
                }

                return {
                    stopOnLastSlide: true,
                    // Has no effect in infinite mode by default.
                    delay: settings.autoplay_speed,
                    disableOnInteraction: true
                };
            },

            handlePauseOnHover: function () {
                if (!$widget.vars.sliderInitialized) return;

                let toggleOn = false;
                if ('yes' === settings.pause_on_hover) {
                    toggleOn = true;
                }

                if ('yes' !== settings.autoplay) {
                    toggleOn = false;
                }
                if (toggleOn) {
                    elements.$swiperContainer.on({
                        mouseenter: () => {
                            swiper.autoplay.stop();
                        },
                        mouseleave: () => {
                            swiper.autoplay.start();
                        }
                    });
                } else {
                    elements.$swiperContainer.off('mouseenter mouseleave');
                }
            },

            getInitialSlide() {
                return 0;
            },

            initSlider: async function () {
                const $slider = elements.$swiperContainer;
                const Swiper = elementorFrontend.utils.swiper;
                swiper = await new Swiper($slider, this.getSwiperOptions()); // Expose the swiper instance in the frontend
                $widget.vars.sliderInitialized = true;
                methods.findAnimationInElements();
                $widget.css('opacity', 1);
                methods.removeElementsAnimation();
                $widget.vars.slideAnimationTimerId = setTimeout(() => {
                    methods.removeElementsAnimation(true);
                    methods.addElementsAnimation();
                }, 300);
                methods.handlePauseOnHover();
                swiper.on('slideChangeTransitionStart', function () {
                });
                swiper.on('slideChangeTransitionEnd', function () {
                    methods.removeElementsAnimation();
                    methods.addElementsAnimation();
                });
            },
            updateSwiperOption: function (propertyName) {
                if (!$widget.vars.sliderInitialized) return;

                const newSettingValue = settings[propertyName];
                let propertyToUpdate = data.changeableProperties[propertyName],
                    valueToUpdate = newSettingValue;
                switch (propertyName) {
                    case 'autoplay_speed':
                        swiper.autoplay.stop();
                        propertyToUpdate = 'autoplay';
                        valueToUpdate = {
                            delay: newSettingValue,
                            disableOnInteraction: true
                        };
                        break;

                    case 'pause_on_hover':
                        methods.handlePauseOnHover()
                        break;

                    case 'autoplay':
                        swiper.autoplay.stop();
                        valueToUpdate = methods.getAutoplayConfig()
                        methods.handlePauseOnHover()
                        break;
                }

                if ('pause_on_hover' !== propertyName) {
                    swiper.params[propertyToUpdate] = valueToUpdate;
                }
                swiper.update();
                if ('autoplay' === propertyToUpdate) {
                    if ('yes' === settings.autoplay) {
                        swiper.autoplay.start();
                    }
                }
            },

            removeElementsAnimation(isForce = false) {
                if (!$widget.vars.sliderInitialized) return;
                clearTimeout($widget.vars.slideAnimationTimerId);
                Object.keys(elements.animatedSlides).forEach(function (slideKey) {
                    if (!isForce && swiper.activeIndex === parseInt(slideKey)) {
                        return;
                    }
                    elements.animatedSlides[slideKey].forEach(function (e) {
                        const $element = $(e.$element);
                        if (!$element.hasClass(data.classes.animated)) {
                            return;
                        }
                        const animation = e.animation;
                        if ('none' === animation) {
                            $element.removeClass(data.classes.elementorInvisible);
                            $element.removeClass(data.classes.the7Hidden);
                        } else {
                            $element.addClass(data.classes.elementorInvisible);
                            $element.addClass(data.classes.the7Hidden);
                        }
                        $element.removeClass(data.classes.animated);
                        $element.removeClass(animation);
                    });
                });
            },

            addElementsAnimation() {
                if (!$widget.vars.sliderInitialized) return;
                let activeSlide = elements.animatedSlides[swiper.activeIndex];

                if (activeSlide === undefined) {
                    return;
                }

                let $activeDuplicates = $(swiper.slides).filter(data.selectors.activeDuplicate);
                $activeDuplicates.each(function (index) {
                    const duplicateIndex = $(swiper.slides).index($(this));
                    const activeDuplicateSlide = elements.animatedSlides[duplicateIndex];
                    if (activeDuplicateSlide !== undefined) {
                        activeSlide = $.merge(activeSlide, activeDuplicateSlide);
                    }
                });

                activeSlide.forEach(function (e) {
                    const $element = $(e.$element);
                    let isAnimated = $element.hasClass(data.classes.animated);
                    if (isAnimated) {
                        return;
                    }
                    const animation = e.animation;
                    const animationDelay = e.animationDelay;
                    if ('none' === animation) {
                        $element.removeClass(data.classes.elementorInvisible).removeClass(data.classes.the7Hidden).addClass(data.classes.animated);
                        return;
                    }
                    $widget.vars.slideAnimationTimerId = setTimeout(() => {
                        $element.removeClass(data.classes.elementorInvisible).removeClass(data.classes.the7Hidden).addClass(data.classes.animated + ' ' + animation);
                    }, animationDelay);
                });
            },

            findAnimationInElements() {
                if (!$widget.vars.sliderInitialized) return;
                let animatedSlides = {};
                $(swiper.slides).each(function (index) {
                        const $slide = $(this);
                        let $elements = $slide.find('.elementor-element');
                        let elementsWithAnimation = [];
                        $elements.each(function () {
                            const $element = $(this);
                            const elemSettings = new The7ElementorSettings($element);
                            const animation = elemSettings.getCurrentDeviceSetting('the7_animation') || elemSettings.getCurrentDeviceSetting('the7__animation');
                            if (!animation) return;
                            const elementSettings = elemSettings.getSettings(),
                                animationDelay = elementSettings._animation_delay || elementSettings.animation_delay || 0;
                            elementsWithAnimation.push({
                                $element: $element,
                                animation: animation,
                                animationDelay: animationDelay
                            });
                        });
                        if (elementsWithAnimation.length) {
                            animatedSlides[index] = elementsWithAnimation;
                        }
                    }
                );
                elements.animatedSlides = animatedSlides;
            }
        };

        //global functions
        $widget.refresh = function () {
            settings = elementorSettings.getSettings();
            methods.unBindEvents();
            methods.bindEvents();
        };
        $widget.delete = function () {
            methods.unBindEvents();
            $widget.removeData("the7Slider");
            if (swiper) swiper.destroy();
        };

        $widget.updateSwiperOption = function (propertyName) {
            settings = elementorSettings.getSettings();
            methods.updateSwiperOption(propertyName);
        }

        methods.init();
    };

    $.fn.the7Slider = function () {
        return this.each(function () {
            var widgetData = $(this).data('the7Slider');
            if (widgetData !== undefined) {
                widgetData.delete();
            }
            new $.the7Slider(this);
        });
    };
});
(function ($) {
    //will prevent elementor native scripts handling
    function patchElementsAnimation(widget) {
        let $elements;
        if (widget === undefined) {
            $elements = $('.elementor-widget-the7-slider .swiper-slide .elementor-element');
        } else {
            $elements = $(widget).find('.elementor-element');
        }
        $elements.each(function () {
            const $element = $(this);
            let settings = $element.data('settings');
            if (typeof settings !== 'undefined' && Object.keys(settings).length) {
                let animationList = The7ElementorSettings.getResponsiveSettingList('animation');
                let _animationList = The7ElementorSettings.getResponsiveSettingList('_animation');
                const list = animationList.concat(_animationList);

                let hasAnimation = false;
                list.forEach(function (item) {
                    if (renameObjProp(settings, item, `the7_${item}`)) {
                        settings[item] = "none";
                        hasAnimation = true;
                    }
                });
                if (hasAnimation) {
                    const $element = $(this);
                    const elemSettings = new The7ElementorSettings($element);
                    const animation = elemSettings.getCurrentDeviceSetting('animation') || elemSettings.getCurrentDeviceSetting('_animation');
                    if (animation) {
                        $element.addClass('the7-slide-hidden');
                    }
                    $element.attr('data-settings', JSON.stringify(settings));
                }
            }
        });
    }

    function renameObjProp(obj, old_key, new_key) {
        if (old_key !== new_key && obj[old_key]) {
            Object.defineProperty(obj, new_key,
                Object.getOwnPropertyDescriptor(obj, old_key));
            delete obj[old_key];
            return true;
        }
        return false;
    }

    // Make sure you run this code under Elementor.
    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction("frontend/element_ready/the7-slider.default", function ($widget, $) {
            $(document).ready(function () {
                if (elementorFrontend.isEditMode()) {
                    patchElementsAnimation($widget);
                }
                $widget.the7Slider();
            })
        });


        if (elementorFrontend.isEditMode()) {
            elementorEditorAddOnChangeHandler("the7-slider", refresh);
        } else {
            patchElementsAnimation();
        }

        function refresh(controlView, widgetView) {
            let refresh_controls = [
                "autoplay_speed",
                "pause_on_hover",
                "autoplay",
                "transition_speed"
            ];
            const controlName = controlView.model.get('name');
            if (-1 !== refresh_controls.indexOf(controlName)) {
                const $widget = $(widgetView.$el);
                const widgetData = $widget.data('the7Slider');
                if (typeof widgetData !== 'undefined') {
                    widgetData.updateSwiperOption(controlName);
                }
            }
        }

    });
})(jQuery);