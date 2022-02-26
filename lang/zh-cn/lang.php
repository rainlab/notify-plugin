<?php

return [
    'plugin' => [
        'name' => '通知',
        'description' => '通知系统',
        'actions' => '操作',
        'conditions' => '条件',
        'settings' => '设置'
    ],
    'notifications' => [
        'menu_label' => '通知规则',
        'menu_description' => '管理触发通知的事件及动作。',
        'name' => '名称',
        'placeholder' => '新通知规则名称',
        'code' => '代码',
        'notification_rule' => '通知规则',
        'add_notification_rule' => '新增通知规则',
        'rule_actions' => '新增通知规则',
        'is_enabled' => '活动',
        'description' => '描述',
        'api_code' => 'API 代码'
    ],
    'action' => [
        'name' => '动作',
        'description' => '动作说明',
        'save' => '保存',
        'add_notification_action' => '新增通知动作',
        'these_variables_are_available' => '这些变量可用',
        'click_or_drag_these' => '单击或将它们拖入内容区域',
        'does_not_provide_any_variables' => '此操作不提供任何变量',
        'on' => '在',
        'add_action' => '添加操作',
        'delete_this_action' => '您真的要删除此操作吗？'
    ],
    'condition' => [
        'name' => '条件',
        'text' => '文本条件',
        'compound_condition' => '复合条件',
        'subconditions_all' => '所有子条件都应为',
        'subconditions_any' => '任意一个子条件应该是',
        'meet_all_subconditions' => '满足所有子条件',
        'meet_any_subconditions' => '满足任意一个子条件',
        'unknown_attribute' => '未知属性',
        'unknown_attribute_selected' => '选择了未知属性',
        'and' => '并且',
        'or' => '或者',
        'is' => '是',
        'is_not' => '不是',
        'equals_or_greater' => '等于或大于',
        'equals_or_less' => '等于或小于',
        'contains' => '包含',
        'does_not_contain' => '不包含',
        'greater' => '大于',
        'less' => '小于',
        'one_of' => '是其中之一',
        'not_one_of' => '不是其中之一',
        'condition_type' => '条件类型',
        'condition' => '所需值',
        'subcondition' => '属性',
        'operator' => '操作',
        'value' => '值',
        'selected_records' => '选定的记录',
        'no_records_added' => '没有添加记录',
        'delete_this_notification_rule' => '要删除此通知规则吗？',
        'new_notification_rule' => '新增通知规则',
        'create' => '创建',
        'please_select_condition' => '请选择条件',
        'delete_this_condition' => '您真的要删除此条件吗？',
        'save' => '保存',
        'add_condition' => '添加条件',
        'delete_condition' => '删除条件'

    ],
    'event' => [
        'name' => '事件',
        'description' => '事件描述',
    ],
    'permissions' => [
        'manage_notifications' => '管理通知功能',
    ],
    'context' => [
        'is' => '是',
        'is_not' => '不是',
        'name' => '事件由环境触发',
        'title' => '执行上下文',
        'backend' => '后台区域',
        'front' => '前端网站',
        'console' => '命令行界面',
        'environment' => '应用环境',
        'context' => '请求上下文',
        'theme' => '活动主题',
        'locale' => '访客语言环境',
        'subcondition' => '属性',
        'operator' => '运算符',
        'value' => '值'
    ],


    // Notify species

    'save_database' => [
        'name' => '存储在数据库中',
        'description' => '在通知活动日志中记录事件数据',
        'related_object' => '相关对象',
        'related_object_text' => '在 :label 日志中记录事件'
    ],

    'send_mail' => [
        'name' => '撰写邮件',
        'description' => '通过Mail向收件人发送消息',
        'title' => '给管理员写邮件',
        'admin_group' => '管理员组',
        'admin_all' => '所有管理员',
        'all_admin_group' => '- 所有管理员组 -',
        'send_message' => '使用模板 %s 向 %s 发送消息',
        'system' => '系统默认',
        'user' => '用户电子邮件地址[user]（如果适用）',
        'sender' => '发件人用户电子邮件地址[sender]（如果适用）',
        'admin' => '后台管理员',
        'custom' => '特定的电子邮件地址',
        'mail_template' => '邮件模板',
        'mail_template_placeholder' => '选择模板 ',
        'send_to_mode' => '发给',
        'send_to_admin' => '发送到管理员组',
        'reply_to_mode' => '回复地址'
    ]

];
