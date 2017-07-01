/*
 * Global helpers
 */
function showActionSettings(id) {
    var $control = $('[data-action-id='+id+']').closest('[data-control="ruleactions"]')

    $control.ruleActions('onShowNewActionSettings', id)
}

/*
 * Plugin definition
 */
+function ($) { "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    var RuleActions = function (element, options) {
        this.$el = $(element)
        this.options = options || {}

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    RuleActions.prototype = Object.create(BaseProto)
    RuleActions.prototype.constructor = RuleActions

    RuleActions.prototype.init = function() {
        this.$el.on('click', '[data-actions-settings]', this.proxy(this.onShowSettings))
        this.$el.on('click', '[data-actions-delete]', this.proxy(this.onDeleteAction))
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    RuleActions.prototype.dispose = function() {
        this.$el.off('click', '[data-actions-settings]', this.proxy(this.onShowSettings))
        this.$el.off('click', '[data-actions-delete]', this.proxy(this.onDeleteAction))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.ruleActions')

        this.$el = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    RuleActions.prototype.onDeleteAction = function(event) {
        var $el = $(event.target),
            actionId = getActionIdFromElement($el)

        $el.request(this.options.deleteHandler, {
            data: { current_action_id: actionId },
            confirm: 'Do you really want to delete this action?'
        })
    }

    RuleActions.prototype.onShowNewActionSettings = function(actionId) {
        var $el = $('[data-action-id='+actionId+']')

        // Action does not use settings
        if ($el.hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_action_id: actionId },
            size: 'giant'
        })

        // This will not fire on successful save because the target element
        // is replaced by the time the popup loader has finished to call it
        $el.one('hide.oc.popup', this.proxy(this.onCancelAction))
    }

    RuleActions.prototype.onCancelAction = function(event) {
        var $el = $(event.target),
            actionId = getActionIdFromElement($el)

        $el.request(this.options.cancelHandler, {
            data: { new_action_id: actionId }
        })

        return false
    }

    RuleActions.prototype.onShowSettings = function(event) {
        var $el = $(event.target),
            actionId = getActionIdFromElement($el)

        // Action does not use settings
        if ($el.closest('li.action-item').hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_action_id: actionId },
            size: 'giant'
        })

        return false
    }

    function getActionIdFromElement($el) {
        var $item = $el.closest('li.action-item')

        return $item.data('action-id')
    }

    RuleActions.DEFAULTS = {
        settingsHandler: null,
        deleteHandler: null,
        cancelHandler: null,
        createHandler: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.ruleActions

    $.fn.ruleActions = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result

        items = this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.ruleActions')
            var options = $.extend({}, RuleActions.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.ruleActions', (data = new RuleActions(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : items
    }

    $.fn.ruleActions.Constructor = RuleActions

    $.fn.ruleActions.noConflict = function () {
        $.fn.ruleActions = old
        return this
    }

    // Add this only if required
    $(document).render(function (){
        $('[data-control="ruleactions"]').ruleActions()
    })

}(window.jQuery);
