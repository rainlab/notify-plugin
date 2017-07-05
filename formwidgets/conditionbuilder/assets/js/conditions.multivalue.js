/*
 * Plugin definition
 */
+function ($) { "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    var ConditionMultiValue = function (element, options) {
        this.$el = $(element)
        this.options = options || {}
        this.$template = $('[data-record-template]', this.$el)
        this.$emptyTemplate = $('[data-empty-record-template]', this.$el)
        this.$addedRecords = $('[data-added-records]', this.$el)

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)

        this.init()
        this.recompileData()
    }

    ConditionMultiValue.prototype = Object.create(BaseProto)
    ConditionMultiValue.prototype.constructor = ConditionMultiValue

    ConditionMultiValue.prototype.init = function() {
        this.$el.on('click', '[data-multivalue-add-record]', this.proxy(this.onAddRecord))
        this.$el.on('click', '[data-multivalue-remove-record]', this.proxy(this.onRemoveRecord))
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    ConditionMultiValue.prototype.dispose = function() {
        this.$el.off('click', '[data-multivalue-add-record]', this.proxy(this.onAddRecord))
        this.$el.off('click', '[data-multivalue-remove-record]', this.proxy(this.onRemoveRecord))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.conditionMultiValue')

        this.$el = null

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    ConditionMultiValue.prototype.onAddRecord = function(event) {
        var $el = $(event.target),
            recordId = $el.closest('[data-record-key]').data('record-key'),
            recordValue = $el.closest('a').data('record-value')

        if (!!$('[data-record-key='+recordId+']', this.$addedRecords).length) {
            return
        }

        this.$addedRecords.append(this.renderTemplate({
            key: recordId,
            value: recordValue
        }))

        this.recompileData()
    }

    ConditionMultiValue.prototype.onRemoveRecord = function(event) {
        var $el = $(event.target),
            recordId = $el.closest('[data-record-key]').data('record-key')

        $('[data-record-key='+recordId+']', this.$addedRecords).remove()

        this.recompileData()
    }

    ConditionMultiValue.prototype.recompileData = function(params) {
        var $recordElements = $('[data-record-key]', this.$addedRecords),
            hasData = !!$recordElements.length

        if (hasData) {
            $('[data-no-record-data]', this.$addedRecords).remove()
        }
        else {
            this.$addedRecords.append(this.renderEmptyTemplate())
        }

        if (this.options.dataLocker) {
            var $locker = $(this.options.dataLocker),
                selectedIds = []

            $recordElements.each(function(key, record) {
                selectedIds.push($(record).data('record-key'))
            })

            $locker.val(selectedIds.join(','))
        }
    }

    ConditionMultiValue.prototype.renderTemplate = function(params) {
        return Mustache.render(this.$template.html(), params)
    }

    ConditionMultiValue.prototype.renderEmptyTemplate = function() {
        return this.$emptyTemplate.html()
    }

    ConditionMultiValue.DEFAULTS = {
        dataLocker: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.conditionMultiValue

    $.fn.conditionMultiValue = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result

        items = this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.conditionMultiValue')
            var options = $.extend({}, ConditionMultiValue.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.conditionMultiValue', (data = new ConditionMultiValue(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : items
    }

    $.fn.conditionMultiValue.Constructor = ConditionMultiValue

    $.fn.conditionMultiValue.noConflict = function () {
        $.fn.conditionMultiValue = old
        return this
    }

    // Add this only if required
    $(document).render(function (){
        $('[data-control="condition-multivalue"]').conditionMultiValue()
    })

}(window.jQuery);
