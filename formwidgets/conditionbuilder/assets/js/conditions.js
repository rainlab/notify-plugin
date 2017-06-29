/*
 * Global helpers
 */
function showConditionSettings(id) {
    var $control = $('[data-condition-id='+id+']').closest('[data-control="ruleconditions"]')

    $control.ruleConditions('onShowNewConditionSettings', id)
}

/*
 * Plugin definition
 */
+function ($) { "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    var RuleConditions = function (element, options) {
        this.$el = $(element)
        this.options = options || {}

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    RuleConditions.prototype = Object.create(BaseProto)
    RuleConditions.prototype.constructor = RuleConditions

    RuleConditions.prototype.init = function() {
        this.$el.on('click', '[data-conditions-collapse]', this.proxy(this.onConditionsToggle))
        this.$el.on('click', '[data-conditions-settings]', this.proxy(this.onShowSettings))
        this.$el.on('click', '[data-conditions-create]', this.proxy(this.onCreateChildCondition))
        this.$el.on('click', '[data-conditions-delete]', this.proxy(this.onDeleteCondition))
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    RuleConditions.prototype.dispose = function() {
        this.$el.off('click', '[data-conditions-collapse]', this.proxy(this.onConditionsToggle))
        this.$el.off('click', '[data-conditions-settings]', this.proxy(this.onShowSettings))
        this.$el.off('click', '[data-conditions-create]', this.proxy(this.onCreateChildCondition))
        this.$el.off('click', '[data-conditions-delete]', this.proxy(this.onDeleteCondition))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.ruleConditions')

        this.$el = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    RuleConditions.prototype.onDeleteCondition = function(event) {
        var $el = $(event.target),
            conditionId = getConditionIdFromElement($el)

        $el.request(this.options.deleteHandler, {
            data: { current_condition_id: conditionId },
            confirm: 'Do you really want to delete this condition?'
        })
    }

    RuleConditions.prototype.onCreateChildCondition = function(event) {
        var $el = $(event.target),
            conditionId = getConditionIdFromElement($el)

        $el.popup({
            handler: this.options.createHandler,
            extraData: { current_condition_id: conditionId },
            size: 'large'
        })

        return false
    }

    RuleConditions.prototype.onConditionsToggle = function(event) {
        var $el = $(event.target),
            $item = $el.closest('li'),
            newStatusValue = $item.hasClass('collapsed') ? 0 : 1,
            conditionId = getConditionIdFromElement($el)

        $el.request(this.options.collapseHandler, {
            data: {
                status: newStatusValue,
                group: conditionId
            }
        })

        if (newStatusValue) {
            $el.parents('li:first').addClass('collapsed');
        }
        else {
            $el.parents('li:first').removeClass('collapsed');
        }

        return false
    }

    RuleConditions.prototype.onShowNewConditionSettings = function(conditionId) {
        var $el = $('[data-condition-id='+conditionId+']')

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_condition_id: conditionId },
            size: 'large'
        })

        // This will not fire on successful save because the target element
        // is replaced by the time the popup loader has finished to call it
        $el.one('hide.oc.popup', this.proxy(this.onCancelCondition))
    }

    RuleConditions.prototype.onCancelCondition = function(event) {
        var $el = $(event.target),
            conditionId = getConditionIdFromElement($el)

        $el.request(this.options.cancelHandler, {
            data: { new_condition_id: conditionId }
        })

        return false
    }

    RuleConditions.prototype.onShowSettings = function(event) {
        var $el = $(event.target),
            conditionId = getConditionIdFromElement($el)

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_condition_id: conditionId },
            size: 'large'
        })

        return false
    }

    function getConditionIdFromElement($el) {
        var $item = $el.closest('li.condition-item')

        return $item.data('condition-id')
    }

    RuleConditions.DEFAULTS = {
        collapseHandler: null,
        settingsHandler: null,
        deleteHandler: null,
        cancelHandler: null,
        createHandler: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.ruleConditions

    $.fn.ruleConditions = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result

        items = this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.ruleConditions')
            var options = $.extend({}, RuleConditions.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.ruleConditions', (data = new RuleConditions(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : items
    }

    $.fn.ruleConditions.Constructor = RuleConditions

    $.fn.ruleConditions.noConflict = function () {
        $.fn.ruleConditions = old
        return this
    }

    // Add this only if required
    $(document).render(function (){
        $('[data-control="ruleconditions"]').ruleConditions()
    })

}(window.jQuery);
