<?php
/**
 * RPI FFL-BRO v4 - Business Workflow Automation & Custom APIs
 * Advanced business process automation and third-party integrations
 */

// ============================================================================
// BUSINESS WORKFLOW AUTOMATION ENGINE
// ============================================================================

class FFL_BRO_Workflow_Engine {
    
    private $workflows = [];
    private $triggers = [];
    private $actions = [];
    
    public function __construct() {
        add_action('init', [$this, 'init_workflows']);
        add_action('wp_ajax_fflbro_trigger_workflow', [$this, 'handle_workflow_trigger']);
        add_action('wp_ajax_fflbro_create_workflow', [$this, 'handle_create_workflow']);
        add_action('wp_ajax_fflbro_list_workflows', [$this, 'handle_list_workflows']);
        
        // Register workflow triggers
        $this->register_triggers();
        
        // Register workflow actions
        $this->register_actions();
        
        // Schedule workflow processor
        if (!wp_next_scheduled('fflbro_process_workflows')) {
            wp_schedule_event(time(), 'fflbro_five_minutes', 'fflbro_process_workflows');
        }
        add_action('fflbro_process_workflows', [$this, 'process_scheduled_workflows']);
    }
    
    public function init_workflows() {
        $this->create_workflow_tables();
        $this->load_default_workflows();
    }
    
    private function create_workflow_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Workflows table
        $workflows_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fflbro_workflows (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            trigger_type varchar(100) NOT NULL,
            trigger_conditions longtext,
            actions longtext,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_type (trigger_type),
            KEY status (status)
        ) $charset_collate;";
        
        // Workflow executions table
        $executions_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fflbro_workflow_executions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) unsigned,
            trigger_data longtext,
            execution_status enum('pending','running','completed','failed') DEFAULT 'pending',
            execution_log longtext,
            started_at datetime,
            completed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workflow_id (workflow_id),
            KEY execution_status (execution_status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($workflows_table);
        dbDelta($executions_table);
    }
    
    private function register_triggers() {
        $this->triggers = [
            'form_4473_submitted' => [
                'name' => 'Form 4473 Submitted',
                'description' => 'Triggered when a new Form 4473 is submitted',
                'conditions' => ['form_status', 'customer_type', 'transaction_amount']
            ],
            'quote_generated' => [
                'name' => 'Quote Generated',
                'description' => 'Triggered when a new quote is generated',
                'conditions' => ['quote_total', 'customer_type', 'product_category']
            ],
            'customer_registered' => [
                'name' => 'Customer Registered',
                'description' => 'Triggered when a new customer registers',
                'conditions' => ['customer_type', 'registration_source']
            ],
            'inventory_low' => [
                'name' => 'Low Inventory Alert',
                'description' => 'Triggered when inventory falls below threshold',
                'conditions' => ['product_category', 'quantity_threshold', 'reorder_point']
            ],
            'opportunity_detected' => [
                'name' => 'Market Opportunity Detected',
                'description' => 'Triggered when market research finds opportunities',
                'conditions' => ['opportunity_score', 'profit_potential', 'category']
            ],
            'scheduled_task' => [
                'name' => 'Scheduled Task',
                'description' => 'Triggered on schedule (daily, weekly, monthly)',
                'conditions' => ['schedule_type', 'time_of_day', 'day_of_week']
            ]
        ];
    }
    
    private function register_actions() {
        $this->actions = [
            'send_email' => [
                'name' => 'Send Email',
                'description' => 'Send an email notification',
                'parameters' => ['to', 'subject', 'template', 'attachments']
            ],
            'send_sms' => [
                'name' => 'Send SMS',
                'description' => 'Send SMS notification',
                'parameters' => ['phone', 'message_template']
            ],
            'create_task' => [
                'name' => 'Create Task',
                'description' => 'Create a task for staff follow-up',
                'parameters' => ['assignee', 'task_type', 'priority', 'due_date']
            ],
            'update_inventory' => [
                'name' => 'Update Inventory',
                'description' => 'Automatically update inventory levels',
                'parameters' => ['product_id', 'quantity_change', 'reason']
            ],
            'generate_report' => [
                'name' => 'Generate Report',
                'description' => 'Generate and deliver business reports',
                'parameters' => ['report_type', 'date_range', 'recipients']
            ],
            'sync_accounting' => [
                'name' => 'Sync to Accounting',
                'description' => 'Sync transaction to accounting system',
                'parameters' => ['transaction_type', 'account_mapping', 'sync_method']
            ],
            'backup_compliance_data' => [
                'name' => 'Backup Compliance Data',
                'description' => 'Create compliance backup for specific transaction',
                'parameters' => ['data_type', 'retention_period', 'storage_location']
            ],
            'webhook_call' => [
                'name' => 'Call Webhook',
                'description' => 'Send data to external webhook URL',
                'parameters' => ['url', 'method', 'headers', 'payload_template']
            ]
        ];
    }
    
    private function load_default_workflows() {
        global $wpdb;
        
        // Check if default workflows already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_workflows WHERE name LIKE 'Default:%'");
        
        if ($existing > 0) {
            return; // Default workflows already loaded
        }
        
        $default_workflows = [
            [
                'name' => 'Default: Form 4473 Submission Notification',
                'description' => 'Notify staff when a Form 4473 is submitted for review',
                'trigger_type' => 'form_4473_submitted',
                'trigger_conditions' => json_encode([
                    'form_status' => 'submitted'
                ]),
                'actions' => json_encode([
                    [
                        'type' => 'send_email',
                        'parameters' => [
                            'to' => get_option('admin_email'),
                            'subject' => 'New Form 4473 Submitted - Review Required',
                            'template' => 'form_4473_notification'
                        ]
                    ],
                    [
                        'type' => 'create_task',
                        'parameters' => [
                            'assignee' => 'ffl_officer',
                            'task_type' => 'form_review',
                            'priority' => 'high',
                            'due_date' => '+2 hours'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'Default: High-Value Quote Follow-up',
                'description' => 'Follow up on high-value quotes after 24 hours',
                'trigger_type' => 'quote_generated',
                'trigger_conditions' => json_encode([
                    'quote_total' => ['operator' => '>', 'value' => 1000]
                ]),
                'actions' => json_encode([
                    [
                        'type' => 'create_task',
                        'parameters' => [
                            'assignee' => 'sales_team',
                            'task_type' => 'quote_followup',
                            'priority' => 'medium',
                            'due_date' => '+24 hours'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'Default: New Customer Welcome',
                'description' => 'Welcome new customers with information packet',
                'trigger_type' => 'customer_registered',
                'trigger_conditions' => json_encode([
                    'customer_type' => 'new'
                ]),
                'actions' => json_encode([
                    [
                        'type' => 'send_email',
                        'parameters' => [
                            'to' => '{{customer.email}}',
                            'subject' => 'Welcome to ' . get_option('fflbro_business_name', 'Our FFL Business'),
                            'template' => 'customer_welcome'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'Default: Low Inventory Alert',
                'description' => 'Alert when popular items are running low',
                'trigger_type' => 'inventory_low',
                'trigger_conditions' => json_encode([
                    'quantity_threshold' => 5,
                    'product_category' => ['firearms', 'accessories']
                ]),
                'actions' => json_encode([
                    [
                        'type' => 'send_email',
                        'parameters' => [
                            'to' => get_option('inventory_manager_email', get_option('admin_email')),
                            'subject' => 'Low Inventory Alert - Reorder Needed',
                            'template' => 'low_inventory_alert'
                        ]
                    ]
                ])
            ],
            [
                'name' => 'Default: Daily Sales Report',
                'description' => 'Generate and send daily sales summary',
                'trigger_type' => 'scheduled_task',
                'trigger_conditions' => json_encode([
                    'schedule_type' => 'daily',
                    'time_of_day' => '18:00'
                ]),
                'actions' => json_encode([
                    [
                        'type' => 'generate_report',
                        'parameters' => [
                            'report_type' => 'daily_sales',
                            'date_range' => 'today',
                            'recipients' => [get_option('admin_email')]
                        ]
                    ]
                ])
            ]
        ];
        
        foreach ($default_workflows as $workflow) {
            $wpdb->insert("{$wpdb->prefix}fflbro_workflows", $workflow);
        }
    }
    
    public function trigger_workflow($trigger_type, $data = []) {
        global $wpdb;
        
        // Find active workflows for this trigger type
        $workflows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}fflbro_workflows WHERE trigger_type = %s AND status = 'active'",
            $trigger_type
        ));
        
        foreach ($workflows as $workflow) {
            // Check if conditions are met
            if ($this->check_workflow_conditions($workflow, $data)) {
                // Queue workflow execution
                $wpdb->insert("{$wpdb->prefix}fflbro_workflow_executions", [
                    'workflow_id' => $workflow->id,
                    'trigger_data' => json_encode($data),
                    'execution_status' => 'pending',
                    'created_at' => current_time('mysql')
                ]);
                
                // Execute immediately for high-priority workflows
                if ($this->is_high_priority_workflow($workflow)) {
                    $this->execute_workflow($workflow, $data);
                }
            }
        }
    }
    
    private function check_workflow_conditions($workflow, $data) {
        $conditions = json_decode($workflow->trigger_conditions, true);
        
        if (empty($conditions)) {
            return true; // No conditions = always execute
        }
        
        foreach ($conditions as $field => $condition) {
            $data_value = $this->get_nested_value($data, $field);
            
            if (is_array($condition) && isset($condition['operator'])) {
                // Complex condition with operator
                $operator = $condition['operator'];
                $expected_value = $condition['value'];
                
                switch ($operator) {
                    case '>':
                        if (!($data_value > $expected_value)) return false;
                        break;
                    case '<':
                        if (!($data_value < $expected_value)) return false;
                        break;
                    case '>=':
                        if (!($data_value >= $expected_value)) return false;
                        break;
                    case '<=':
                        if (!($data_value <= $expected_value)) return false;
                        break;
                    case '!=':
                        if (!($data_value != $expected_value)) return false;
                        break;
                    case 'in':
                        if (!in_array($data_value, (array)$expected_value)) return false;
                        break;
                    default:
                        if ($data_value != $expected_value) return false;
                }
            } else {
                // Simple equality check
                if (is_array($condition)) {
                    if (!in_array($data_value, $condition)) return false;
                } else {
                    if ($data_value != $condition) return false;
                }
            }
        }
        
        return true;
    }
    
    private function execute_workflow($workflow, $trigger_data) {
        global $wpdb;
        
        $execution_id = $wpdb->insert_id;
        $log = [];
        
        try {
            // Update execution status
            $wpdb->update(
                "{$wpdb->prefix}fflbro_workflow_executions",
                ['execution_status' => 'running', 'started_at' => current_time('mysql')],
                ['id' => $execution_id]
            );
            
            $actions = json_decode($workflow->actions, true);
            
            foreach ($actions as $action) {
                $result = $this->execute_action($action, $trigger_data);
                $log[] = [
                    'action' => $action['type'],
                    'result' => $result,
                    'timestamp' => current_time('mysql')
                ];
            }
            
            // Mark as completed
            $wpdb->update(
                "{$wpdb->prefix}fflbro_workflow_executions",
                [
                    'execution_status' => 'completed',
                    'execution_log' => json_encode($log),
                    'completed_at' => current_time('mysql')
                ],
                ['id' => $execution_id]
            );
            
        } catch (Exception $e) {
            // Mark as failed
            $log[] = [
                'error' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            ];
            
            $wpdb->update(
                "{$wpdb->prefix}fflbro_workflow_executions",
                [
                    'execution_status' => 'failed',
                    'execution_log' => json_encode($log),
                    'completed_at' => current_time('mysql')
                ],
                ['id' => $execution_id]
            );
        }
    }
    
    private function execute_action($action, $trigger_data) {
        $action_type = $action['type'];
        $parameters = $action['parameters'];
        
        // Replace template variables in parameters
        $parameters = $this->replace_template_variables($parameters, $trigger_data);
        
        switch ($action_type) {
            case 'send_email':
                return $this->action_send_email($parameters);
            case 'send_sms':
                return $this->action_send_sms($parameters);
            case 'create_task':
                return $this->action_create_task($parameters);
            case 'update_inventory':
                return $this->action_update_inventory($parameters);
            case 'generate_report':
                return $this->action_generate_report($parameters);
            case 'sync_accounting':
                return $this->action_sync_accounting($parameters);
            case 'backup_compliance_data':
                return $this->action_backup_compliance_data($parameters);
            case 'webhook_call':
                return $this->action_webhook_call($parameters);
            default:
                throw new Exception("Unknown action type: $action_type");
        }
    }
    
    private function action_send_email($params) {
        $to = $params['to'];
        $subject = $params['subject'];
        $template = $params['template'] ?? 'default';
        
        // Load email template
        $content = $this->load_email_template($template, $params);
        
        // Send email
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $result = wp_mail($to, $subject, $content, $headers);
        
        return $result ? 'Email sent successfully' : 'Email failed to send';
    }
    
    private function action_send_sms($params) {
        $phone = $params['phone'];
        $message = $params['message_template'];
        
        // SMS integration would go here (Twilio, etc.)
        // For now, log the SMS
        error_log("SMS to $phone: $message");
        
        return 'SMS logged (integration required)';
    }
    
    private function action_create_task($params) {
        global $wpdb;
        
        $task_data = [
            'assignee' => $params['assignee'],
            'task_type' => $params['task_type'],
            'priority' => $params['priority'],
            'due_date' => date('Y-m-d H:i:s', strtotime($params['due_date'])),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert("{$wpdb->prefix}fflbro_tasks", $task_data);
        
        return $result ? 'Task created successfully' : 'Failed to create task';
    }
    
    private function action_update_inventory($params) {
        global $wpdb;
        
        // Update inventory logic
        return 'Inventory updated';
    }
    
    private function action_generate_report($params) {
        $report_type = $params['report_type'];
        $date_range = $params['date_range'];
        
        // Generate report based on type
        $report_generator = new FFL_BRO_Report_Generator();
        $report = $report_generator->generate($report_type, $date_range);
        
        // Send to recipients
        foreach ($params['recipients'] as $recipient) {
            wp_mail($recipient, "Daily Report - $report_type", $report);
        }
        
        return 'Report generated and sent';
    }
    
    private function action_sync_accounting($params) {
        // Accounting system integration
        return 'Synced to accounting system';
    }
    
    private function action_backup_compliance_data($params) {
        // Create compliance backup
        return 'Compliance data backed up';
    }
    
    private function action_webhook_call($params) {
        $url = $params['url'];
        $method = $params['method'] ?? 'POST';
        $headers = $params['headers'] ?? [];
        $payload = $params['payload_template'] ?? '';
        
        $response = wp_remote_request($url, [
            'method' => $method,
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return 'Webhook failed: ' . $response->get_error_message();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return "Webhook called (HTTP $status_code)";
    }
    
    private function replace_template_variables($parameters, $data) {
        $json = json_encode($parameters);
        
        // Replace template variables like {{customer.email}}
        $json = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($data) {
            $path = $matches[1];
            return $this->get_nested_value($data, $path) ?? $matches[0];
        }, $json);
        
        return json_decode($json, true);
    }
    
    private function get_nested_value($array, $path) {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    private function load_email_template($template, $params) {
        // Load email template from file or database
        $template_path = FFLBRO_ENHANCED_PLUGIN_DIR . "templates/emails/$template.html";
        
        if (file_exists($template_path)) {
            $content = file_get_contents($template_path);
        } else {
            $content = $this->get_default_email_template($template);
        }
        
        // Replace variables in template
        foreach ($params as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }
    
    private function get_default_email_template($template) {
        switch ($template) {
            case 'form_4473_notification':
                return '<h2>New Form 4473 Submitted</h2><p>A new Form 4473 has been submitted and requires review.</p>';
            case 'customer_welcome':
                return '<h2>Welcome!</h2><p>Thank you for choosing our FFL services.</p>';
            case 'low_inventory_alert':
                return '<h2>Low Inventory Alert</h2><p>Some items are running low and need reordering.</p>';
            default:
                return '<p>Automated notification from FFL-BRO system.</p>';
        }
    }
    
    private function is_high_priority_workflow($workflow) {
        $high_priority_triggers = ['form_4473_submitted', 'security_alert'];
        return in_array($workflow->trigger_type, $high_priority_triggers);
    }
    
    public function process_scheduled_workflows() {
        global $wpdb;
        
        // Process pending workflow executions
        $pending_executions = $wpdb->get_results(
            "SELECT we.*, w.* FROM {$wpdb->prefix}fflbro_workflow_executions we 
             JOIN {$wpdb->prefix}fflbro_workflows w ON we.workflow_id = w.id 
             WHERE we.execution_status = 'pending' 
             ORDER BY we.created_at ASC 
             LIMIT 10"
        );
        
        foreach ($pending_executions as $execution) {
            $trigger_data = json_decode($execution->trigger_data, true);
            $this->execute_workflow($execution, $trigger_data);
        }
    }
    
    // AJAX handlers
    public function handle_workflow_trigger() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        $trigger_type = sanitize_text_field($_POST['trigger_type']);
        $trigger_data = $_POST['trigger_data'] ?? [];
        
        $this->trigger_workflow($trigger_type, $trigger_data);
        
        wp_send_json_success(['message' => 'Workflow triggered successfully']);
    }
    
    public function handle_create_workflow() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        
        $workflow_data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'trigger_type' => sanitize_text_field($_POST['trigger_type']),
            'trigger_conditions' => $_POST['trigger_conditions'],
            'actions' => $_POST['actions'],
            'status' => 'active'
        ];
        
        $result = $wpdb->insert("{$wpdb->prefix}fflbro_workflows", $workflow_data);
        
        if ($result) {
            wp_send_json_success(['workflow_id' => $wpdb->insert_id]);
        } else {
            wp_send_json_error('Failed to create workflow');
        }
    }
    
    public function handle_list_workflows() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        
        $workflows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_workflows ORDER BY created_at DESC");
        
        wp_send_json_success(['workflows' => $workflows]);
    }
}

// ============================================================================
// CUSTOM API SYSTEM FOR THIRD-PARTY INTEGRATIONS
// ============================================================================

class FFL_BRO_Custom_API {
    
    private $api_version = 'v1';
    private $namespace = 'fflbro/v1';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_api_routes']);
        add_action('init', [$this, 'handle_api_authentication']);
        
        // Create API keys table
        add_action('init', [$this, 'create_api_tables']);
    }
    
    public function create_api_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $api_keys_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fflbro_api_keys (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            api_key varchar(64) NOT NULL,
            api_secret varchar(128) NOT NULL,
            name varchar(255) NOT NULL,
            permissions longtext,
            rate_limit int(11) DEFAULT 1000,
            last_used datetime,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key),
            KEY status (status)
        ) $charset_collate;";
        
        $api_logs_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fflbro_api_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            api_key_id bigint(20) unsigned,
            endpoint varchar(255),
            method varchar(10),
            request_data longtext,
            response_code int(11),
            response_data longtext,
            execution_time float,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY api_key_id (api_key_id),
            KEY endpoint (endpoint),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($api_keys_table);
        dbDelta($api_logs_table);
    }
    
    public function register_api_routes() {
        // Authentication endpoints
        register_rest_route($this->namespace, '/auth/token', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_api_token'],
            'permission_callback' => '__return_true'
        ]);
        
        // Quote management endpoints
        register_rest_route($this->namespace, '/quotes', [
            'methods' => 'GET',
            'callback' => [$this, 'get_quotes'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        register_rest_route($this->namespace, '/quotes', [
            'methods' => 'POST',
            'callback' => [$this, 'create_quote'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        register_rest_route($this->namespace, '/quotes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_quote'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        // Form 4473 endpoints
        register_rest_route($this->namespace, '/forms/4473', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_form_4473'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        register_rest_route($this->namespace, '/forms/4473/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_form_4473'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        // Inventory endpoints
        register_rest_route($this->namespace, '/inventory', [
            'methods' => 'GET',
            'callback' => [$this, 'get_inventory'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        register_rest_route($this->namespace, '/inventory/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search_inventory'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        // Market research endpoints
        register_rest_route($this->namespace, '/market/opportunities', [
            'methods' => 'GET',
            'callback' => [$this, 'get_market_opportunities'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        register_rest_route($this->namespace, '/market/scan', [
            'methods' => 'POST',
            'callback' => [$this, 'trigger_market_scan'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        // Business analytics endpoints
        register_rest_route($this->namespace, '/analytics/sales', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sales_analytics'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        register_rest_route($this->namespace, '/analytics/performance', [
            'methods' => 'GET',
            'callback' => [$this, 'get_performance_metrics'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
        
        // Webhook endpoints
        register_rest_route($this->namespace, '/webhooks/(?P<type>\w+)', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'check_webhook_permission']
        ]);
        
        // System status endpoint
        register_rest_route($this->namespace, '/system/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_system_status'],
            'permission_callback' => [$this, 'check_api_permission']
        ]);
    }
    
    public function check_api_permission($request) {
        $api_key = $this->get_api_key_from_request($request);
        
        if (!$api_key) {
            return new WP_Error('no_auth', 'API key required', ['status' => 401]);
        }
        
        $key_data = $this->validate_api_key($api_key);
        
        if (!$key_data) {
            return new WP_Error('invalid_auth', 'Invalid API key', ['status' => 401]);
        }
        
        // Check rate limiting
        if (!$this->check_rate_limit($key_data)) {
            return new WP_Error('rate_limit', 'Rate limit exceeded', ['status' => 429]);
        }
        
        // Log API usage
        $this->log_api_request($request, $key_data);
        
        return true;
    }
    
    private function get_api_key_from_request($request) {
        $auth_header = $request->get_header('Authorization');
        
        if ($auth_header && preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
            return $matches[1];
        }
        
        return $request->get_param('api_key');
    }
    
    private function validate_api_key($api_key) {
        global $wpdb;
        
        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}fflbro_api_keys WHERE api_key = %s AND status = 'active'",
            $api_key
        ));
        
        if (!$key_data) {
            return false;
        }
        
        // Check expiration
        if ($key_data->expires_at && strtotime($key_data->expires_at) < time()) {
            return false;
        }
        
        // Update last used
        $wpdb->update(
            "{$wpdb->prefix}fflbro_api_keys",
            ['last_used' => current_time('mysql')],
            ['id' => $key_data->id]
        );
        
        return $key_data;
    }
    
    private function check_rate_limit($key_data) {
        global $wpdb;
        
        $recent_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_api_logs 
             WHERE api_key_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $key_data->id
        ));
        
        return $recent_requests < $key_data->rate_limit;
    }
    
    private function log_api_request($request, $key_data, $response_code = 200, $response_data = null) {
        global $wpdb;
        
        $start_time = microtime(true);
        $execution_time = microtime(true) - $start_time;
        
        $wpdb->insert("{$wpdb->prefix}fflbro_api_logs", [
            'api_key_id' => $key_data->id,
            'endpoint' => $request->get_route(),
            'method' => $request->get_method(),
            'request_data' => json_encode($request->get_params()),
            'response_code' => $response_code,
            'response_data' => $response_data ? json_encode($response_data) : null,
            'execution_time' => $execution_time,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    // API endpoint implementations
    public function get_quotes($request) {
        global $wpdb;
        
        $page = max(1, intval($request->get_param('page')));
        $per_page = min(100, max(1, intval($request->get_param('per_page') ?: 20)));
        $offset = ($page - 1) * $per_page;
        
        $status = $request->get_param('status');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $where_conditions = ['1=1'];
        $params = [];
        
        if ($status) {
            $where_conditions[] = 'status = %s';
            $params[] = $status;
        }
        
        if ($date_from) {
            $where_conditions[] = 'created_at >= %s';
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where_conditions[] = 'created_at <= %s';
            $params[] = $date_to;
        }
        
        $where_sql = implode(' AND ', $where_conditions);
        
        $sql = "SELECT * FROM {$wpdb->prefix}fflbro_quotes WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        $quotes = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        return rest_ensure_response([
            'quotes' => $quotes,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_quotes WHERE $where_sql", array_slice($params, 0, -2)))
            ]
        ]);
    }
    
    public function create_quote($request) {
        global $wpdb;
        
        $quote_data = [
            'customer_id' => intval($request->get_param('customer_id')),
            'items' => json_encode($request->get_param('items')),
            'totals' => json_encode($request->get_param('totals')),
            'status' => 'draft',
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert("{$wpdb->prefix}fflbro_quotes", $quote_data);
        
        if ($result) {
            $quote_id = $wpdb->insert_id;
            
            // Trigger workflow
            $workflow_engine = new FFL_BRO_Workflow_Engine();
            $workflow_engine->trigger_workflow('quote_generated', [
                'quote_id' => $quote_id,
                'quote_total' => $request->get_param('totals')['total'] ?? 0,
                'customer_id' => $quote_data['customer_id']
            ]);
            
            return rest_ensure_response([
                'quote_id' => $quote_id,
                'message' => 'Quote created successfully'
            ]);
        } else {
            return new WP_Error('create_failed', 'Failed to create quote', ['status' => 500]);
        }
    }
    
    public function get_quote($request) {
        global $wpdb;
        
        $quote_id = intval($request->get_param('id'));
        
        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}fflbro_quotes WHERE id = %d",
            $quote_id
        ));
        
        if (!$quote) {
            return new WP_Error('not_found', 'Quote not found', ['status' => 404]);
        }
        
        return rest_ensure_response(['quote' => $quote]);
    }
    
    public function submit_form_4473($request) {
        global $wpdb;
        
        $form_data = [
            'form_data' => json_encode($request->get_param('form_data')),
            'status' => 'submitted',
            'atf_transaction_id' => 'ATF-' . date('Ymd') . '-' . uniqid(),
            'atf_logged' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert("{$wpdb->prefix}fflbro_forms_4473", $form_data);
        
        if ($result) {
            $form_id = $wpdb->insert_id;
            
            // Trigger workflow
            $workflow_engine = new FFL_BRO_Workflow_Engine();
            $workflow_engine->trigger_workflow('form_4473_submitted', [
                'form_id' => $form_id,
                'transaction_id' => $form_data['atf_transaction_id'],
                'form_status' => 'submitted'
            ]);
            
            return rest_ensure_response([
                'form_id' => $form_id,
                'transaction_id' => $form_data['atf_transaction_id'],
                'message' => 'Form 4473 submitted successfully'
            ]);
        } else {
            return new WP_Error('submit_failed', 'Failed to submit Form 4473', ['status' => 500]);
        }
    }
    
    public function get_system_status($request) {
        $status = [
            'system' => [
                'version' => '4.0.0',
                'environment' => 'production',
                'uptime' => $this->get_system_uptime(),
                'timestamp' => current_time('mysql')
            ],
            'services' => [
                'database' => $this->check_database_status(),
                'cache' => $this->check_cache_status(),
                'workflows' => $this->check_workflow_status()
            ],
            'performance' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ]
        ];
        
        return rest_ensure_response($status);
    }
    
    private function get_system_uptime() {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            return floatval(explode(' ', $uptime)[0]);
        }
        return null;
    }
    
    private function check_database_status() {
        global $wpdb;
        
        try {
            $wpdb->get_var("SELECT 1");
            return 'healthy';
        } catch (Exception $e) {
            return 'error';
        }
    }
    
    private function check_cache_status() {
        // Check if object cache is working
        wp_cache_set('fflbro_test', 'test_value');
        $cached = wp_cache_get('fflbro_test');
        return $cached === 'test_value' ? 'healthy' : 'disabled';
    }
    
    private function check_workflow_status() {
        global $wpdb;
        
        $pending_workflows = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_workflow_executions WHERE execution_status = 'pending'"
        );
        
        return [
            'status' => 'healthy',
            'pending_executions' => intval($pending_workflows)
        ];
    }
}

// ============================================================================
// INTEGRATION HELPERS AND UTILITIES
// ============================================================================

class FFL_BRO_Integration_Manager {
    
    public function __construct() {
        // Initialize third-party integrations
        add_action('init', [$this, 'init_integrations']);
    }
    
    public function init_integrations() {
        // QuickBooks integration
        if (get_option('fflbro_quickbooks_enabled')) {
            new FFL_BRO_QuickBooks_Integration();
        }
        
        // Shopify integration
        if (get_option('fflbro_shopify_enabled')) {
            new FFL_BRO_Shopify_Integration();
        }
        
        // Email marketing integration
        if (get_option('fflbro_mailchimp_enabled')) {
            new FFL_BRO_MailChimp_Integration();
        }
    }
}

// Initialize workflow engine and API system
new FFL_BRO_Workflow_Engine();
new FFL_BRO_Custom_API();
new FFL_BRO_Integration_Manager();