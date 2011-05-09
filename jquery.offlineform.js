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
            base.action = base.$el.attr("action") || window.location.href;
            base.method = base.$el.attr("method") || "post";
            base.document = $(document);
            base.checkbox = base.$el.find('[type=checkbox]');
            base.multipleSelect = base.$el.find('select[multiple]');

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
                var ancien = base.getOfflineData(true);

                // Save form data in localStorage
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
        };

        // Function to submit data
        base.handleDataToSubmit = function(){
            var formulaire = base.getOfflineData();
            if(formulaire) {
                var new_formulaire = {};
                $.each(formulaire, function(i, v) {
                    // Send form to server
                    if(i == base.name) {
                        // Get the content of the request
                        base.boundary = new Date().getTime();
                        var content = base.getMultipartContent(v.value);
                        // Check if this form has some files to upload
                        if(v.files && v.files.length)
                            content+= base.getMultipartContent(v.files, true);
                        content+= "--"+base.boundary+"--\r\n";

                        // Send the request
                        $.ajax({
                            type:base.method,
                            url:base.action,
                            data:content,
                            contentType: "multipart/form-data; boundary="+base.boundary,
                            error:function() {
                                new_formulaire[i] = v;
                            }
                        });

                        // Retrieve and replace one last time data transmitted
                        base.handleOfflineData();
                        if(base.options.dataSubmittedEvent)
                            base.$el.trigger(base.options.dataSubmittedEvent);
                    }
                    else
                        new_formulaire[i] = v;
                });
                base.set_form_to_submit(new_formulaire);
            }
        };

        // Function to recover offline data (in case we are viewing a cached page)
        base.handleOfflineData = function(){
            var ancien = base.getOfflineData();
            if(ancien && ancien[base.name]) {
                if(base.checkbox.length)
                    base.checkbox.prop('checked', false);
                if(base.multipleSelect.length)
                    base.multipleSelect.find('option').prop('selected', false);
                var val = ancien[base.name].value;
                $.each(val, function(i,v) {
                    var cleanName = v.name.replace(/\]/g, "\\\]").replace(/\[/g, "\\\["), input = base.$el.find("[name="+cleanName+"]");
                    if(input.is(':checkbox') || input.is(':radio'))
                        base.$el.find('[name='+cleanName+'][value=' + v.value + ']').prop('checked', true);
                    else if(input.is('select') && input.prop('multiple'))
                        input.find('[value=' + v.value + ']').prop('selected', true);
                    else
                        input.val(v.value);
                });
            }
        };

        // Function to retrieve data from localStorage
        base.getOfflineData = function(setDefault) {
            var data = localStorage.getItem('form_to_submit') || '{}';
            // Does localStorage store anything ?
            if(data.length > 2 || setDefault)
                data = JSON.parse(data);
            else
                data = false;

            return data;
        };

        // Function to get content of a xhr request with content-type "multipart"
        base.getMultipartContent = function(value, file) {
            var content = [], i = 0;
            $.each(value, function(k, v) {
                content[i] = "--"+base.boundary+"\r\n";
                content[i]+= "Content-Disposition: form-data; name='"+v.name+"'";
                if(file) {
                    content[i]+= "\r\n";
                }
                else
                    content[i]+= "\r\n";
                content[i]+= "\r\n";
                content[i]+= v.value+"\r\n";
                ++i;
            });

            return content.join('');
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
