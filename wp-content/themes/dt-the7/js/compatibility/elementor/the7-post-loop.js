(function ($) {
    $.the7PostLoop = function (el) {
        const data = {
            selectors: {
                gridContainer: '.sGrid-container',
                filterContainer: '.filter',
                filterCategories: '.filter-categories'
            },
            classes: {
                inPlaceTemplateEditable: "elementor-in-place-template-editable"
            }
        };

        let $widget = $(el),
            $wrapper = $widget.find('.the7-elementor-widget').first(),
            elementorSettings,
            settings,
            methods,
            widgetType,
            elements = {
                $gridContainer: $wrapper.children(data.selectors.gridContainer).first(),
                $filterContainer: $wrapper.children(data.selectors.filterContainer).first(),
                $filterCategories: $wrapper.children(data.selectors.filterCategories).first(),
            };
        $widget.vars = {
            masonryActive: false,
            filteradeActive: false,
            isInlineEditing: false,
            effectsTimer: null,
            effectsTimerEnable:false
        };

        // Store a reference to the object
        $.data(el, "the7PostLoop", $widget);
        // Private methods
        methods = {
            init: function () {
                elementorSettings = new The7ElementorSettings($widget);
                widgetType = elementorSettings.getWidgetType();
                settings = elementorSettings.getSettings();
                methods.bindEvents();
                $widget.refresh();
                if (elementorFrontend.isEditMode()) {
                    methods.handleCTA();
                    // Filter active item class handling since it's not included in filtrade.
                    window.the7ApplyGeneralFilterHandlers(elements.$filterCategories);
                }
                $widget.refresh = elementorFrontend.debounce($widget.refresh, 300);
            },
            initFilter: function () {
                let config = methods.getFilterConfig()
                if (!$widget.vars.filteradeActive) {
                    elements.$gridContainer.The7SimpleFilterade(config);
                    $widget.vars.filteradeActive = true;
                }
                else{
                    elements.$gridContainer.The7SimpleFilterade('update',config);
                }
            },
            getFilterConfig: function(){
                let config = the7ShortcodesFilterConfig(elements.$filterContainer);

                config.paginationMode = $wrapper.attr("data-pagination-mode");

                config.pageLimit = $wrapper.attr("data-post-limit");
                config.pageControls = $wrapper.children(".paginator, .paginator-more-button");

                config.pagesToShow = $wrapper.hasClass("show-all-pages") ? 999 : 5;

                config.pagerClass = "page-numbers filter-item";
                config.nodesSelector = '> .wf-cell'

                config.usePaginationScroll = settings['pagination_scroll'] === 'y';
                config.scrollPagesOffset = settings['pagination_scroll_offset'] ? settings['pagination_scroll_offset'].size : 0

                config.infinityScroll = $wrapper.hasClass("lazy-loading-mode");

                return config;
            },
            handleLoadingEffects: function () {
                if ($widget.vars.effectsTimerEnable) {
                    clearTimeout($widget.vars.effectsTimer);
                    $widget.vars.effectsTimer = setTimeout(function () {
                        methods.processEffects();
                        elementorFrontend.elements.$window.on('scroll', methods.handleLoadingEffects);
                    }, 500)
                }
                else{
                    methods.processEffects();
                }
            },

            processEffects: function () {
                $elements = elements.$gridContainer.children('.wf-cell:not(.shown):not(.is-visible)');
                window.the7ProcessEffects($elements);
            },
            handleCTA: function () {
                if (elementorPro === 'undefined') {
                    return;
                }
                const emptyViewContainer = document.querySelector(`[data-id="${elementorSettings.getID()}"] .e-loop-empty-view__wrapper`);
                const emptyViewContainerOld = document.querySelector(`[data-id="${elementorSettings.getID()}"] .e-loop-empty-view__wrapper_old`);

                if (emptyViewContainerOld) {
                    $widget.css('opacity', 1);
                    return;
                }

                if (!emptyViewContainer) {
                    return;
                }

                const shadowRoot = emptyViewContainer.attachShadow({
                    mode: 'open'
                });
                shadowRoot.appendChild(elementorPro.modules.loopBuilder.getCtaStyles());
                shadowRoot.appendChild(elementorPro.modules.loopBuilder.getCtaContent(widgetType));
                const ctaButton = shadowRoot.querySelector('.e-loop-empty-view__box-cta');
                ctaButton.addEventListener('click', () => {
                    elementorPro.modules.loopBuilder.createTemplate();
                    methods.handlePostEdit();
                });
                $widget.css('opacity', 1);
            },
            bindEvents: function () {
                elementorFrontend.elements.$window.on('scroll', methods.handleLoadingEffects);
                elementorFrontend.elements.$window.on('the7-resize-width', methods.handleResize);

                elements.$gridContainer.on('beforeSwitchPage', methods.onBeforeSwitchPage);
                elements.$gridContainer.on('updateReady', methods.onFilteradeUpdateReady);
            },
            unBindEvents: function () {
                elementorFrontend.elements.$window.off('scroll', methods.handleLoadingEffects);
                elementorFrontend.elements.$window.off('the7-resize-width', methods.handleResize);

                elements.$gridContainer.off('updateReady', methods.onFilteradeUpdateReady);
            },
            onBeforeSwitchPage:function() {
                elementorFrontend.elements.$window.off('scroll', methods.handleLoadingEffects);
                $widget.vars.effectsTimerEnable = true;
            },
            onFilteradeUpdateReady:function() {
                methods.handleLoadingEffects();
                methods.updateSimpleMasonry();
            },
            handleResize: function () {
                if (methods.isMasonryEnabled()) {
                    if (!$widget.vars.masonryActive) {
                        elements.$gridContainer.The7SimpleMasonry(methods.getMasonryConfig());
                        $widget.vars.masonryActive = true;
                    }
                    else {
                        methods.updateSimpleMasonry();
                    }
                } else {
                    if ($widget.vars.masonryActive) {
                        elements.$gridContainer.The7SimpleMasonry('destroy');
                        $widget.vars.masonryActive = false;
                    }
                }
            },
            updateSimpleMasonry(){
                if ($widget.vars.masonryActive){
                    elements.$gridContainer.The7SimpleMasonry('setSettings', methods.getMasonryConfig());
                }
            },

            getMasonryConfig: function () {
                return {
                    items: '.wf-cell.visible',
                    columnsCount: +The7ElementorSettings.getResponsiveControlValue(settings, 'columns', 'size') || 0,
                    verticalSpaceBetween: +The7ElementorSettings.getResponsiveControlValue(settings, 'rows_gap', 'size') || 0
                }
            },
            isMasonryEnabled: function () {
                return !!settings['layout'];
            },

            handlePostEdit(){
                $widget.vars.isInlineEditing = true;
                $widget.addClass(data.classes.inPlaceTemplateEditable);
            },

            updateOption: function (propertyName) {
                const newSettingValue = settings[propertyName];
            },
        };

        //global functions
        $widget.refresh = function () {
            settings = elementorSettings.getSettings();
            methods.initFilter();
            methods.handleResize();
        };
        $widget.delete = function () {
            methods.unBindEvents();
            $widget.removeData("the7PostLoop");
        };

        $widget.onDocumentLoaded = function (document) {
            if (document.config.type === 'loop-item') {
                methods.handlePostEdit();

                let elementsToRemove = [];
                const templateID = document.id;
                elementsToRemove = [...elementsToRemove, 'style#loop-' + templateID, 'link#font-loop-' + templateID, 'style#loop-dynamic-' + templateID];
                elementsToRemove.forEach(elementToRemove => {
                    $widget.find(elementToRemove).remove();
                });
            }
        }

        methods.init();
    };

    $.fn.the7PostLoop = function () {
        return this.each(function () {
            var widgetData = $(this).data('the7PostLoop');
            if (widgetData !== undefined) {
                widgetData.delete();
            }
            new $.the7PostLoop(this);
        });
    };


    // Make sure you run this code under Elementor.
    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction("frontend/element_ready/the7-post-loop.post", widgetHandler);

        function widgetHandler($widget, $) {
            $(document).ready(function () {
                $widget.the7PostLoop();
            })
        }

        if (elementorFrontend.isEditMode()) {
            elementorEditorAddOnChangeHandler("the7-post-loop", refresh);
            elementor.on("document:loaded", onDocumentLoaded);
        }

        function onDocumentLoaded(document) {
            var $elements = $('.elementor-widget-the7-post-loop');
            $elements.each(function () {
                const $widget = $(this);
                const widgetData = $widget.data('the7PostLoop');
                if (typeof widgetData !== 'undefined') {
                    widgetData.onDocumentLoaded(document);
                }
            });
        }

        function refresh(controlView, widgetView) {
            let refresh_controls = [
                'layout',
                ...The7ElementorSettings.getResponsiveSettingList('columns'),
                ...The7ElementorSettings.getResponsiveSettingList('rows_gap'),
                ...The7ElementorSettings.getResponsiveSettingList('columns_gap'),
                'pagination_scroll_offset',
                'pagination_scroll'
            ];
            const controlName = controlView.model.get('name');
            if (-1 !== refresh_controls.indexOf(controlName)) {
                const $widget = $(widgetView.$el);
                const widgetData = $widget.data('the7PostLoop');
                if (typeof widgetData !== 'undefined') {
                    widgetData.refresh(controlName);
                }
            }
        }
    });
})(jQuery);