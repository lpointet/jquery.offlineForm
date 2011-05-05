(function($){
    $.offlineForm = function(el, options){
        // To avoid scope issues, use 'base' instead of 'this'
        // to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("offlineForm", base);

        base.init = function(){
            base.options = $.extend({},$.offlineForm.defaultOptions, options);

            base.name = base.$el.attr("id");
            base.action = base.$el.attr("action") || window.location.href,
            base.method = base.$el.attr("method") || "post",
            base.document = $(document);
            base.checkbox = base.$el.find('[type=checkbox]');

            // Handle submit event
            base.$el.submit(base.handleOfflineForm);

            // If we are online => submit registered values
            if(window.navigator.onLine)
                base.handleDataToSubmit();
            // Otherwise => replace the cache values with registered ones
            else
                base.handleOfflineData();
        };

        // Callback for submit event
        base.handleOfflineForm = function(e){
            if(!window.navigator.onLine) {
                var ancien = localStorage.getItem('form_to_submit') || '{}';

                // Save form data in localStorage
                ancien = JSON.parse(ancien);
                ancien[base.name] = {'value' : base.$el.serializeArray(), 'action' : base.action};
                base.set_form_to_submit(ancien);

                // Trigger event
                if(base.options.offlineSubmitEvent)
                    base.$el.trigger(base.options.offlineSubmitEvent);
                // Go to top
                base.document.scrollTop(0);
                // Blur (to hide keyboard on iPhone for example)
                base.$el.find(':focus').blur();

                // Block submission
                return false;
            }
        };

        // Function to store data
        base.set_form_to_submit = function(value) {
            localStorage.setItem('form_to_submit', JSON.stringify(value));
        }

        // Function to submit data
        base.handleDataToSubmit = function(){
            var formulaire = localStorage.getItem('form_to_submit') || '{}';
            // Does localStorage store anything ?
            if(formulaire.length > 2) {
                formulaire = JSON.parse(formulaire);
                var new_formulaire = {};
                $.each(formulaire, function(i, v) {
                    // Send form to server
                    if(i == base.name) {
                        $.ajax({type:base.method, url:base.action, data:v.value, error:function() {
                            new_formulaire[i] = v;
                        }});
                        // Retrieve and replace one last time data transmitted
                        base.handleOfflineData();
                    }
                    else
                        new_formulaire[i] = v;
                });
                base.set_form_to_submit(new_formulaire);
                if(base.options.dataSubmittedEvent)
                    base.$el.trigger(base.options.dataSubmittedEvent);
            }
        };

        // Function to recover offline data (in case we are viewing a cached page)
        base.handleOfflineData = function(){
            var ancien = localStorage.getItem('form_to_submit') || '{}';
            if(ancien.length > 2) {
                ancien = JSON.parse(ancien);
                if(ancien[base.name]) {
                    if(base.checkbox.length)
                        base.checkbox.prop('checked', false);
                    var val = ancien[base.name].value;
                    $.each(val, function(i,v) {
                        var input = base.$el.find("[name="+v.name+"]");
                        if(input.is(':checkbox'))
                            base.$el.find('[name='+v.name+'][value=' + v.value + ']').prop('checked', true);
                        else
                            input.val(v.value);
                    });
                }
            }
        };

        // Run initializer
        base.init();
    };

    $.offlineForm.defaultOptions = {
        offlineSubmitEvent: null,
        dataSubmittedEvent: null
    };

    $.fn.offlineForm = function(options){
        return this.each(function(){
            (new $.offlineForm(this, options));
        });
    };

})(jQuery);
