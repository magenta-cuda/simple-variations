<?php                                                                                                                                //       0
/*                                                                                                                                   //       1
 * Plugin Name: Simple Variations for Classic Commerce                                                                               //       2
 * Plugin URI: http://svmc.x10host.com/                                                                                              //       3
 * Description: Implements the variations of a variable product as the Cartesian product of the attributes.                          //       4
 * Version: 0.1.1                                                                                                                    //       5
 * Author: Magenta Cuda                                                                                                              //       6
 * Author URI: http://m-cuda.dx.am/                                                                                                  //       7
 *                                                                                                                                   //       8
 * This program is distributed in the hope that it will be useful,                                                                   //       9
 * but WITHOUT ANY WARRANTY; without even the implied warranty of                                                                    //      10
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                                                                              //      11
 *                                                                                                                                   //      12
 * This is a development mule for prototyping variations of a Classic Commerce variable product.                                     //      13
 * The payment gateways are disabled.                                                                                                //      14
 */                                                                                                                                  //      15
                                                                                                                                     //      16

### REDACTED lines   17 ->   18 redacted,      2 lines redacted. ###

                                                                                                                                     //      19
# Sanitize and log the request for debugging                                                                                         //      20
                                                                                                                                     //      21
if ( ! ( strpos( $_SERVER["REQUEST_URI"], '/wp-admin/admin-ajax.php' ) !== FALSE                                                     //      22
    && array_key_exists( 'action', $_REQUEST ) && $_REQUEST[ 'action'] === 'heartbeat') ) {                                          //      23
    error_log( '$_SERVER["REQUEST_URI"] = ' . $_SERVER['REQUEST_URI'] );                                                             //      29
    error_log( '$_REQUEST               = ' . trim( preg_replace( [ '/^Array$/m', '/^\($/m', '/^\)$/m' ], '',                        //      30
                                                                  print_r( array_filter( $_REQUEST, function( $key ) {               //      31
                                                                      return in_array( $key, [ 'action', 'doing_wp_cron' ] );        //      32
                                                                  }, ARRAY_FILTER_USE_KEY ), TRUE ) ), "\n" ) );                     //      33
}                                                                                                                                    //      34
                                                                                                                                     //      35

### REDACTED lines   36 ->   56 redacted,     21 lines redacted. ###

                                                                                                                                     //      57
class MC_Die_Handler {                                                                                                               //      58
    public $handler;                                                                                                                 //      59
    public $message;                                                                                                                 //      60
    public $title;                                                                                                                   //      61
    public $args;                                                                                                                    //      62
}                                                                                                                                    //      63
                                                                                                                                     //      64
class MC_Utility {                                                                                                                   //      65
    const TRANSIENT_NOTICES       = 'mc_xii_transient_notices';                                                                      //      66
    private static $hook_handlers = [];                                                                                              //      67
                                                                                                                                     //      68
    # Anonymous functions are convenient and because they are implemented using the Closure class can capture context from where the //      69
    # anonymous function is created. However, when used with the WordPress add_filter() function they cannot be easily removed.      //      70
    # MC_Utility::add_filter() is a wrapper for add_filter() that binds the anonymous function to a string handle so that it can be  //      71
    # removed using the string handle.                                                                                               //      72
                                                                                                                                     //      73
    public static function add_filter( $tag, $handle, $function_to_add, $priority = 10, $accepted_args = 1 ) {                       //      74
        if ( array_key_exists( $handle, self::$hook_handlers ) ) {                                                                   //      75
            error_log( "ERROR: @MC_Utility::add_filter():{$handle} already exists!" );                                               //      79
            error_log( "ERROR: @MC_Utility::add_filter():BACKTRACE = \n"                                                             //      80
                               . str_replace( ', ', "\n", wp_debug_backtrace_summary() ) );                                          //      81
        }                                                                                                                            //      82
        self::$hook_handlers[ $handle ] = $function_to_add;                                                                          //      83
        return add_filter( $tag, $function_to_add, $priority, $accepted_args );                                                      //      84
    }                                                                                                                                //      85
                                                                                                                                     //      86
    public static function remove_filter( $tag, $handle, $priority = 10 ) {                                                          //      87
        $function_to_remove = self::$hook_handlers[ $handle ];                                                                       //      88
        return remove_filter( $tag, $function_to_remove, $priority );                                                                //      89
    }                                                                                                                                //      90
                                                                                                                                     //      91
    public static function add_action( $tag, $handle, $function_to_add, $priority = 10, $accepted_args = 1 ) {                       //      92
        if ( array_key_exists( $handle, self::$hook_handlers ) ) {                                                                   //      93
            error_log( "ERROR: @MC_Utility::add_action():'{$handle}' already exists!" );                                             //      97
            error_log( "ERROR: @MC_Utility::add_action():BACKTRACE = \n"                                                             //      98
                               . str_replace( ', ', "\n", wp_debug_backtrace_summary() ) );                                          //      99
        }                                                                                                                            //     100
        self::$hook_handlers[ $handle ] = $function_to_add;                                                                          //     101
        return add_action( $tag, $function_to_add, $priority, $accepted_args );                                                      //     102
    }                                                                                                                                //     103
                                                                                                                                     //     104
    public static function remove_action( $tag, $handle, $priority = 10 ) {                                                          //     105
        $function_to_remove = self::$hook_handlers[ $handle ];                                                                       //     106
        return remove_action( $tag, $function_to_remove, $priority );                                                                //     107
    }                                                                                                                                //     108
                                                                                                                                     //     109
    public static function get_hook( $handle ) {                                                                                     //     110
        if ( array_key_exists( $handle, self::$hook_handlers ) ) {                                                                   //     111
            return self::$hook_handlers[ $handle ];                                                                                  //     112
        } else {                                                                                                                     //     113
            return NULL;                                                                                                             //     114
        }                                                                                                                            //     115
    }                                                                                                                                //     116
                                                                                                                                     //     117
    # In order to wrap a function that calls wp_die() the call to wp_die() must be nullified otherwise the wrapped function will     //     118
    # never return and you cannot post process the result of the the call to the wrapped function.                                   //     119
                                                                                                                                     //     120
    #    $die_call = &MC_Utility::postpone_wp_die();                                                                                 //     121
    #    ...                                                                                                                         //     122
    #    the_wrapped_function();                                                                                                     //     123
    #    ...                                                                                                                         //     124
    #    MC_Utility::do_postponed_wp_die( $die_call );                                                                               //     125
                                                                                                                                     //     126
    public static function &postpone_wp_die() {                                                                                      //     127
        $callback = new MC_Die_Handler();                                                                                            //     128
        add_filter( 'wp_die_ajax_handler', function( $die_handler ) use ( &$callback ) {                                             //     129
            $callback->handler = $die_handler;                                                                                       //     131
            return function( $message, $title, $args )  use ( &$callback ) {                                                         //     132
                $callback->message = $message;                                                                                       //     134
                $callback->title   = $title;                                                                                         //     135
                $callback->args    = $args;                                                                                          //     136
            };                                                                                                                       //     137
        }, PHP_INT_MAX );                                                                                                            //     138
        add_filter( 'wp_die_handler', function( $die_handler ) use ( &$callback ) {                                                  //     139
            $callback->handler = $die_handler;                                                                                       //     141
            return function( $message, $title, $args ) use ( &$callback ) {                                                          //     142
                $callback->message = $message;                                                                                       //     144
                $callback->title   = $title;                                                                                         //     145
                $callback->args    = $args;                                                                                          //     146
            };                                                                                                                       //     147
        }, PHP_INT_MAX );                                                                                                            //     148
        return $callback;                                                                                                            //     149
    }                                                                                                                                //     150
                                                                                                                                     //     151
    public static function do_postponed_wp_die( $die_call ) {                                                                        //     152
        call_user_func( $die_call->handler, $die_call->message, $die_call->title, $die_call->args );                                 //     154
    }                                                                                                                                //     155
                                                                                                                                     //     156
    # TODO: maybe WC_Admin_Meta_Boxes::add_error() will work in place of add_transient_admin_notice()                                //     157
    public static function add_transient_admin_notice( $notice, $class = 'info' ) {                                                  //     158
        ob_start();                                                                                                                  //     159
?>                                                                                                                                   <!--   160 -->
<div class="notice notice-<?php echo $class; ?> is-dismissible">                                                                     <!--   161 -->
    <p><?php echo $notice; ?></p>                                                                                                    <!--   162 -->
</div>                                                                                                                               <!--   163 -->
<?php                                                                                                                                //     164
        $notice  = ob_get_contents();                                                                                                //     165
        ob_end_clean();                                                                                                              //     166
        $notices = get_transient( self::TRANSIENT_NOTICES );                                                                         //     167
        if ( $notices === FALSE ) {                                                                                                  //     168
            $notices = [ $notice ];                                                                                                  //     169
        } else {                                                                                                                     //     170
            $notices[] = $notice;                                                                                                    //     171
        }                                                                                                                            //     172
        set_transient( self::TRANSIENT_NOTICES, $notices );                                                                          //     173
    }   # public static function add_transient_admin_notice( $notice, $class = 'info' ) {                                            //     174
                                                                                                                                     //     175
    public static function do_transient_admin_notices() {                                                                            //     176
        $notices = get_transient( self::TRANSIENT_NOTICES );                                                                         //     177
        if ( $notices === FALSE ) {                                                                                                  //     178
            return;                                                                                                                  //     179
        }                                                                                                                            //     180
        $notices_to_do = count( $notices );                                                                                          //     181
        foreach ( $notices as $notice ) {                                                                                            //     182
            add_action( 'admin_notices', function() use ( $notice, &$notices_to_do ) {                                               //     183
                echo $notice;                                                                                                        //     184
                if ( ! --$notices_to_do ) {                                                                                          //     185
                    delete_transient( self::TRANSIENT_NOTICES );                                                                     //     186
                }                                                                                                                    //     187
            } );                                                                                                                     //     188
        }                                                                                                                            //     189
    }   # public static function do_transient_admin_notices() {                                                                      //     190
                                                                                                                                     //     191
    # If a notice is generated after the 'admin_notices' action has already been done then add the notice to the queue.              //     192
    # Also useful to queue admin notices while processing AJAX requests.                                                             //     193
    public static function do_admin_notice( $notice, $class = 'info', $force = FALSE ) {                                             //     194
        if ( $force || did_action( 'admin_notices' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {                                //     195
            self::add_transient_admin_notice( $notice, $class );                                                                     //     196
        } else {                                                                                                                     //     197
            add_action( 'admin_notices', function() use ( $notice, $class ) {                                                        //     198
?>                                                                                                                                   <!--   199 -->
<div class="notice notice-<?php echo $class; ?> is-dismissible">                                                                     <!--   200 -->
    <p><?php echo $notice; ?></p>                                                                                                    <!--   201 -->
</div>                                                                                                                               <!--   202 -->
<?php                                                                                                                                //     203
            } );                                                                                                                     //     204
        }                                                                                                                            //     205
    }   # public static function do_admin_notice( $notice, $class = 'info' ) {                                                       //     206
                                                                                                                                     //     207
    public static function sideload_image( $file_name, $desc = NULL, $post_data = [] ) {                                             //     208
        require_once(ABSPATH . 'wp-admin/includes/media.php');                                                                       //     209
        require_once(ABSPATH . 'wp-admin/includes/file.php');                                                                        //     210
        require_once(ABSPATH . 'wp-admin/includes/image.php');                                                                       //     211
        $slash_pos              = strrpos( $file_name, '/' );                                                                        //     212
        $file_array             = [];                                                                                                //     213
        $file_array['name']     = $slash_pos === FALSE ? $file_name : substr( $file_name, $slash_pos + 1 );                          //     214
        $file_array['tmp_name'] = wp_tempnam();                                                                                      //     215
        copy( plugin_dir_path( __FILE__ ) . $file_name, $file_array['tmp_name'] );                                                   //     216
        clearstatcache();                                                                                                            //     217
        return media_handle_sideload( $file_array, 0, $desc, $post_data );                                                           //     218
        # return media_handle_sideload( [ 'name' => $file_name, 'tmp_name' => plugin_dir_path( __FILE__ ) . $file_name ], 0 );       //     219
        # above will not work as media_handle_sideload() will unlink() the 'tmp_name' file                                           //     220
    }   # public static function sideload_image( $file_name, $desc = NULL, $post_data = [] ) {                                       //     221
                                                                                                                                     //     222
}   # class MC_Utility {                                                                                                             //     223
                                                                                                                                     //     224
# MC_Hook_Wrapper provides a paradigm for wrapping WordPress actions and filters. The difference between wrapping and WordPress      //     225
# priority based hooks is priority based hooks are called sequentially by priority but wrapped hooks are nested calls allowing       //     226
# processing before and after the call to the nested hook.                                                                           //     227
                                                                                                                                     //     228
class MC_Hook_Wrapper {                                                                                                              //     229
                                                                                                                                     //     230
    private $wrapper;                                                                                                                //     231
    private $callback;         # binds a callback to this object                                                                     //     232
    private $is_action;        # is this an action or filter                                                                         //     233
    private $priority;                                                                                                               //     234
    private $accepted_args;                                                                                                          //     235
                                                                                                                                     //     236
    public function __construct( $wrapper, $callback, $is_action = FALSE, $priority = 10, $accepted_args = 1 ) {                     //     237
        $this->wrapper       = $wrapper;                                                                                             //     238
        $this->callback      = $callback;                                                                                            //     239
        $this->is_action     = $is_action;                                                                                           //     240
        $this->priority      = $priority;                                                                                            //     241
        $this->accepted_args = $accepted_args;                                                                                       //     242
    }                                                                                                                                //     243
                                                                                                                                     //     244
    public function call_wrapper( ...$args ) {                                                                                       //     246
        return call_user_func_array( $this->wrapper, array_merge( [ $this->callback ], $args ) );                                    //     248
    }                                                                                                                                //     249
                                                                                                                                     //     250
    public function get_callback() {                                                                                                 //     251
        return $this->callback;                                                                                                      //     252
    }                                                                                                                                //     253
                                                                                                                                     //     254
    public function is_action() {                                                                                                    //     255
        return $this->is_action;                                                                                                     //     256
    }                                                                                                                                //     257
                                                                                                                                     //     258
    public function get_priority() {                                                                                                 //     259
        return $this->priority;                                                                                                      //     260
    }                                                                                                                                //     261
                                                                                                                                     //     262
    public function get_accepted_args() {                                                                                            //     263
        return $this->accepted_args;                                                                                                 //     264
    }                                                                                                                                //     265
                                                                                                                                     //     266
    # the $wrapper argument in wrap_hook() should be defined like this:                                                              //     267
    #                                                                                                                                //     268
    # $wrapper = function( $callback, ...$args ) {                                                                                   //     269
    #     ...                                                                                                                        //     270
    #     $ret = call_user_func_array( $callback, $args );                                                                           //     271
    #     ...                                                                                                                        //     272
    #     return $ret;                                                                                                               //     273
    # }                                                                                                                              //     274
                                                                                                                                     //     275
    public static function wrap_hook( $handle, $wrapper, $tags, $callback, $is_action = FALSE, $priority = 10,                       //     276
                                      $accepted_args = 1 ) {                                                                         //     277
        $tags = is_array( $tags ) ? $tags : [ $tags ];                                                                               //     278
        foreach ( $tags as $tag ) {                                                                                                  //     279
            # $wrapper_obj is an object which is bound to the original callback                                                      //     280
            $wrapper_obj          = new self( $wrapper, $callback, $is_action, $priority, $accepted_args );                          //     281
            if ( ! remove_filter( $tag, $callback, $priority ) ) {                                                                   //     282
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //     283
                                   "ERROR: MC_Hook_Wrapper::wrap_hook():hook for \"$tag\" not installed, callback is: "              //     284
                                       . print_r( $callback, TRUE ), 'SV 0.1.0' );                                                   //     285
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //     286
                                   'ERROR: MC_Hook_Wrapper::wrap_hook():must be called after the hook is installed.', 'SV 0.1.0' );  //     287
                return FALSE;                                                                                                        //     288
            }                                                                                                                        //     289
            if ( $is_action ) {                                                                                                      //     290
                MC_Utility::add_action( $tag, "{$handle}-{$tag}", [ $wrapper_obj, 'call_wrapper' ], $priority, $accepted_args );     //     291
            } else {                                                                                                                 //     292
                MC_Utility::add_filter( $tag, "{$handle}-{$tag}", [ $wrapper_obj, 'call_wrapper' ], $priority, $accepted_args );     //     293
            }                                                                                                                        //     294
        }                                                                                                                            //     295
        return TRUE;                                                                                                                 //     296
    }   # public static function wrap_hook( $handle, $wrapper, $tag, $callback, $is_action = FALSE, $priority = 10, $accepted_args = //     297
                                                                                                                                     //     298
    public static function unwrap_hook( $tag, $handle ) {                                                                            //     299
        if ( $wrapper_obj = MC_Utility::get_hook( $handle ) ) {                                                                      //     300
            if ( $wrapper_obj->is_action() ) {                                                                                       //     301
                MC_Utility::remove_action( $tag, $handle, $wrapper_obj->get_priority() );                                            //     302
                add_action( $tag, $wrapper_obj->get_callback(), $wrapper_obj->get_priority(), $wrapper_obj->get_accepted_args() );   //     303
            } else {                                                                                                                 //     304
                MC_Utility::remove_filter( $tag, $handle, $wrapper_obj->get_priority() );                                            //     305
                add_filter( $tag, $wrapper_obj->get_callback(), $wrapper_obj->get_priority(), $wrapper_obj->get_accepted_args() );   //     306
            }                                                                                                                        //     307
        }                                                                                                                            //     308
    }   # public static function unwrap_hook( $tag, $handle ) {                                                                      //     309
                                                                                                                                     //     310
}   # class MC_Hook_Wrapper {                                                                                                        //     311
                                                                                                                                     //     312
# A difficulty with WordPress actions and filters is that the parameters given to the hook often does not provide sufficient         //     313
# context. MC_Context allows (maybe indirect) callers of hooks to pass context to the hooks.                                         //     314
                                                                                                                                     //     315
#   try {                                                                                                                            //     316
#       MC_Context::push( 'some_key', 'some_value' );                                                                                //     317
#       call_some_hook_maybe_indirectly();                                                                                           //     318
#   } finally {                                                                                                                      //     319
#       MC_Context::pop();                                                                                                           //     320
#   }                                                                                                                                //     321
#                                                                                                                                    //     322
#   or take advantage of MC_Context::__destruct() for objects that live on the stack                                                 //     323
#                                                                                                                                    //     324
#   $context = new MC_Context( $key, $value );                                                                                       //     325
#                                                                                                                                    //     326
                                                                                                                                     //     327
class MC_Context {                                                                                                                   //     328
                                                                                                                                     //     329
    private static $stack = [];                                                                                                      //     330
    private static $map   = [];   # allows fast access to the stack by key                                                           //     331
    private static $heap  = [];   # global values                                                                                    //     332
                                                                                                                                     //     333
    private $index;                                                                                                                  //     334
                                                                                                                                     //     335
    public function __construct( $key, $value ) {                                                                                    //     336
        $this->index = self::push( $key, $value );                                                                                   //     337
        self::dump( '@MC_Context::__construct()' );                                                                                  //     338
    }                                                                                                                                //     339
                                                                                                                                     //     340
    public function __destruct() {                                                                                                   //     341
        self::pop_to( $this->index );                                                                                                //     342
        self::dump( '@MC_Context::__destruct()' );                                                                                   //     343
    }                                                                                                                                //     344
                                                                                                                                     //     345
    public static function push( $key, $value ) {                                                                                    //     346
        # handle multiple values with the same key                                                                                   //     347
        self::$map[ $key ][] = array_push( self::$stack, [ $key, $value ] ) - 1;                                                     //     348
        return count( self::$stack ) - 1;                                                                                            //     349
    }                                                                                                                                //     350
                                                                                                                                     //     351
    public static function pop() {                                                                                                   //     352
        array_pop( self::$map[ array_pop( self::$stack )[ 0 ] ] );                                                                   //     353
    }                                                                                                                                //     354
                                                                                                                                     //     355
    public static function pop_to( $i ) {                                                                                            //     356
        for ( ; count( self::$stack ) > $i; ) {                                                                                      //     357
            self::pop();                                                                                                             //     358
        }                                                                                                                            //     359
    }                                                                                                                                //     360
    public static function pop_to_key( $key ) {                                                                                      //     361
        self::pop_to( end( self::$map[ $key ] ) );                                                                                   //     362
    }                                                                                                                                //     363
                                                                                                                                     //     364
    public static function get( $key ) {                                                                                             //     365
        # return the topmost value on the stack with key                                                                             //     366
        if ( ! empty( self::$map[ $key ] ) ) {                                                                                       //     367
            return self::$stack[ end( self::$map[ $key ] ) ][ 1 ];                                                                   //     368
        }                                                                                                                            //     369
        # next try the heap                                                                                                          //     370
        if ( array_key_exists( $key, self::$heap ) ) {                                                                               //     371
            return self::$heap[ $key ];                                                                                              //     372
        }                                                                                                                            //     373
        return NULL;                                                                                                                 //     374
    }                                                                                                                                //     375
                                                                                                                                     //     376
    public static function set( $key, $value ) {                                                                                     //     377
        self::$heap[ $key ] = $value;                                                                                                //     378
    }                                                                                                                                //     379
                                                                                                                                     //     380
    public static function clr( $key ) {                                                                                             //     381
        unset( self::$heap[ $key ] );                                                                                                //     382
    }                                                                                                                                //     383
                                                                                                                                     //     384
    public static function dump( $where = '' ) {                                                                                     //     385
    }                                                                                                                                //     389
                                                                                                                                     //     390
}   # class MC_Context {                                                                                                             //     391
                                                                                                                                     //     392
class MC_Execution_Time {                                                                                                            //     393
                                                                                                                                     //     394
    private static $getrusage_exists          = FALSE;                                                                               //     395
    private static $max_execution_time;                                                                                              //     396
    private static $timer_interval_resolution = 0;         # max time between successive calls to near_max_execution_limit().        //     397
    private static $prev_execution_time       = 0;                                                                                   //     398
    private static $test_max_execution_time   = 0;         # set using the $wpdb->options option                                     //     399
                                                           #     'mc_xii_simple_variations_test_max_execution_time'                  //     400
                                                           # update cp_options set option_value = 3000 where option_name             //     401
                                                           #     = 'mc_xii_simple_variations_test_max_execution_time';               //     402
                                                           # delete from cp_options where option_name                                //     403
                                                           #     = 'mc_xii_simple_variations_test_max_execution_time';               //     404
    private static $time                      = 'time';                                                                              //     405
    private static $debug                     = 0x0000;    # php wp-cli.phar eval                                                    //     406
                                                           #         'update_option("mc_xii_simple_variations_debug_log", 0x0001);'  //     407
                                                                                                                                     //     408
    private static function get_rusage() {                                                                                           //     409
        if ( self::$getrusage_exists ) {                                                                                             //     411
            $usage = getrusage();                                                                                                    //     412
            return ( $usage['ru_utime.tv_sec'] * 1000000 + $usage['ru_utime.tv_usec']                                                //     413
                       + $usage['ru_stime.tv_sec'] * 1000000 + $usage['ru_stime.tv_usec'] ) - $GLOBALS['mc_xii_rusage_0'];           //     414
        }                                                                                                                            //     415
        return 0;                                                                                                                    //     416
    }                                                                                                                                //     417
                                                                                                                                     //     418
    public static function get_execution_time() {                                                                                    //     419
        $execution_time = self::get_rusage();                                                                                        //     420
        if ( ! self::$timer_interval_resolution && self::$prev_execution_time ) {                                                    //     421
            self::$timer_interval_resolution = 2 * ( $execution_time - self::$prev_execution_time );                                 //     423
            update_option( 'mc_xii_timer_interval_resolution', self::$timer_interval_resolution );                                   //     424
        } else if ( ($interval = $execution_time - self::$prev_execution_time ) > self::$timer_interval_resolution / 2 ) {           //     426
            # Adjust self::$timer_interval_resolution if the interval between successive calls to this function is greater than      //     427
            # self::$timer_interval_resolution                                                                                       //     428
            self::$timer_interval_resolution = 2 * $interval;                                                                        //     430
            update_option( 'mc_xii_timer_interval_resolution', self::$timer_interval_resolution );                                   //     431
        }                                                                                                                            //     433
        self::$prev_execution_time = $execution_time;                                                                                //     434
        return $execution_time;                                                                                                      //     435
    }                                                                                                                                //     436
                                                                                                                                     //     437
    public static function near_max_execution_limit( $share = 1 ) {                                                                  //     438
        if ( self::$getrusage_exists ) {                                                                                             //     439
            $execution_time = self::get_execution_time();                                                                            //     440
            if ( self::$timer_interval_resolution ) {                                                                                //     441
                $return = $share * self::$max_execution_time - $execution_time < self::$timer_interval_resolution;                   //     442
                if ( self::$debug | 0x0001 && $return ) {                                                                            //     443
                    error_log( '@MC_Execution_Time::near_max_execution_limit():$execution_time                  = '                  //     444
                            . $execution_time );                                                                                     //     445
                    error_log( '@MC_Execution_Time::near_max_execution_limit():self::$max_execution_time        = '                  //     446
                            . self::$max_execution_time );                                                                           //     447
                    error_log( '@MC_Execution_Time::near_max_execution_limit():self::$timer_interval_resolution = '                  //     448
                            . self::$timer_interval_resolution );                                                                    //     449
                    error_log( "@MC_Execution_Time::near_max_execution_limit():BACKTRACE = \n"                                       //     450
                            . str_replace( ', ', "\n", wp_debug_backtrace_summary() ) );                                             //     451
                }                                                                                                                    //     452
                return $return;                                                                                                      //     453
            }                                                                                                                        //     454
        }                                                                                                                            //     455
        return FALSE;                                                                                                                //     456
    }                                                                                                                                //     457
                                                                                                                                     //     458
    private static function set_max_execution_time() {                                                                               //     459
        $max_execution_time = (integer) ini_get( 'max_execution_time' ) * 1000000;                                                   //     460
        // TODO: remove this override                                                                                                //     462
        if ( $max_execution_time === 0 ) {                                                                                           //     463
            $max_execution_time = 30000000;                                                                                          //     464
        }                                                                                                                            //     465
        $max_execution_time *= 0.9;                                                                                                  //     466
        # if self::$test_max_execution_time has been set use it instead                                                              //     467
        if ( self::$test_max_execution_time > 0 && self::$test_max_execution_time < $max_execution_time ) {                          //     468
            $max_execution_time = self::$test_max_execution_time;                                                                    //     469
        }                                                                                                                            //     470
        self::$max_execution_time = $max_execution_time;                                                                             //     472
    }                                                                                                                                //     473
                                                                                                                                     //     474
    public static function set_test_max_execution_time( $max_execution_time ) {                                                      //     475
        self::$test_max_execution_time = $max_execution_time;                                                                        //     476
        self::set_max_execution_time();                                                                                              //     477
    }                                                                                                                                //     478
                                                                                                                                     //     479
    # time() returns time stamps in float seconds                                                                                    //     480
                                                                                                                                     //     481
    public static function time() {                                                                                                  //     482
        switch ( self::$time ) {                                                                                                     //     483
        case 'getrusage':                                                                                                            //     484
            return self::get_rusage() / 1000000;                                                                                     //     485
        case 'microtime':                                                                                                            //     486
            return microtime( TRUE ) / 1000000;                                                                                      //     487
        default:                                                                                                                     //     488
            return (float) time();                                                                                                   //     489
        }                                                                                                                            //     490
    }                                                                                                                                //     491
                                                                                                                                     //     492
    public static function calc_elapsed_times( $time_stamps ) {                                                                      //     493
        $elapsed_times = [];                                                                                                         //     494
        for ( $i = 1; $i < count( $time_stamps ); $i++ ) {                                                                           //     495
            $elapsed_times[] = $time_stamps[ $i ] - $time_stamps[ $i - 1 ];                                                          //     496
        }                                                                                                                            //     497
        # last item in $elapsed_times is the total of of all intervals.                                                              //     498
        $elapsed_times[] = array_sum( $elapsed_times );                                                                              //     499
        return $elapsed_times;                                                                                                       //     500
    }   # public static function calc_elapsed_times( $time_stamps ) {                                                                //     501
                                                                                                                                     //     502
    public static function sum_elapsed_times( $elapsed_times, $additional_elapsed_times ) {                                          //     503
        # last item in $elapsed_times is the number of times sum_elapsed_times() has been called.                                    //     504
        $additional_elapsed_times[] = 1;                                                                                             //     505
        if ( empty( $elapsed_times ) ) {                                                                                             //     506
            return $additional_elapsed_times;                                                                                        //     507
        }                                                                                                                            //     508
        for ( $i = 0; $i < count( $elapsed_times ); $i++ ) {                                                                         //     509
            $elapsed_times[ $i ] += $additional_elapsed_times[ $i ];                                                                 //     510
        }                                                                                                                            //     511
        return $elapsed_times;                                                                                                       //     512
    }   # public static function sum_elapsed_times( $elapsed_times, $additional_elapsed_times ) {                                    //     513
                                                                                                                                     //     514
    public static function init() {                                                                                                  //     515
        self::$debug = get_option( 'mc_xii_simple_variations_debug_log', 0x0000 );                                                   //     516
        if ( self::$getrusage_exists = function_exists( 'getrusage' ) ) {                                                            //     517
            self::$prev_execution_time       = self::get_rusage();                                                                   //     518
            self::$timer_interval_resolution = get_option( 'mc_xii_timer_interval_resolution', 0 );                                  //     520
            self::set_max_execution_time();                                                                                          //     521
        }                                                                                                                            //     522
        self::$time = self::$getrusage_exists ? 'getrusage' : ( function_exists( 'microtime' ) ? 'microtime' : 'time' );             //     523
    }                                                                                                                                //     526
                                                                                                                                     //     527
}   # class MC_Execution_Time {                                                                                                      //     528
                                                                                                                                     //     529
MC_Execution_Time::init();                                                                                                           //     530
                                                                                                                                     //     531
# MC_Array_Of_Arrays is used to store a map of product attribute values to values as a multi-dimensional array where each dimension  //     532
# corresponds to a product attribute. The value of a leaf node is the variation with the attribute values. The difficulty is the     //     533
# number of attributes is varying.                                                                                                   //     534
                                                                                                                                     //     535
class MC_Array_Of_Arrays {                                                                                                           //     536
                                                                                                                                     //     537
    private $dimensions;                                                                                                             //     538
    private $data;                                                                                                                   //     539
                                                                                                                                     //     540
    # make_array_of_arrays() returns the array of arrays in $aa. $a is an array of arrays of attribute values. E.g., for a variable  //     541
    # product with attributes color and size $a could be [ ['red', 'blue', 'green'], ['small', 'medium', 'large'] ].                 //     542
                                                                                                                                     //     543
    private static function make_array_of_arrays( &$aa, $a ) {                                                                       //     544
        $aa = [];                                                                                                                    //     545
        foreach ( current( $a ) as $k ) {                                                                                            //     546
            $aa[ $k ] = NULL;                                                                                                        //     547
            if ( next( $a ) ) {                                                                                                      //     548
                self::make_array_of_arrays( $aa[ $k ], $a );                                                                         //     549
            }                                                                                                                        //     550
            prev( $a );                                                                                                              //     551
        }                                                                                                                            //     552
    }                                                                                                                                //     553
                                                                                                                                     //     554
    # Traverse the array of arrays $aa of dimension $d and apply the function $f to the leaf nodes. $a is an array of the keys of    //     555
    # the nodes currently being traversed. $aa is passed by reference so it can be modified. If $f returns false the traversal is    //     556
    # stopped.                                                                                                                       //     557
                                                                                                                                     //     558
    private static function walk_array_of_arrays( &$aa, $d, $a, $f ) {                                                               //     559
        if ( count( $a ) < $d && is_array( $aa ) ) {                                                                                 //     560
            foreach ( $aa as $k => &$v ) {                                                                                           //     561
                array_push( $a, $k );                                                                                                //     562
                $continue = self::walk_array_of_arrays( $v, $d, $a, $f );                                                            //     563
                array_pop( $a );                                                                                                     //     564
                if ( ! $continue ) {                                                                                                 //     565
                    return FALSE;                                                                                                    //     566
                }                                                                                                                    //     567
            }                                                                                                                        //     568
        } else {                                                                                                                     //     569
            if ( ! call_user_func_array( $f, [ &$aa, $a ] ) ) {                                                                      //     570
                return FALSE;                                                                                                        //     571
            }                                                                                                                        //     572
        }                                                                                                                            //     573
        return TRUE;                                                                                                                 //     574
    }                                                                                                                                //     575
                                                                                                                                     //     576
    public function __construct( $a ) {                                                                                              //     577
        $this->dimensions = count( $a );                                                                                             //     578
        self::make_array_of_arrays( $this->data, $a );                                                                               //     579
    }                                                                                                                                //     580
                                                                                                                                     //     581
    # Traverse the array of arrays and apply the function $f to the leaf nodes.                                                      //     582
                                                                                                                                     //     583
    public function walk( $f ) {                                                                                                     //     584
        return self::walk_array_of_arrays( $this->data, $this->dimensions, [], $f );                                                 //     585
    }                                                                                                                                //     586
                                                                                                                                     //     587
    # Traverse the array of arrays using the path specified by $a and return the leaf node by reference.                             //     588
                                                                                                                                     //     589
    public function &get_item( $a ) {                                                                                                //     590
        $v =& $this->data;                                                                                                           //     591
        foreach ( $a as $k ) {                                                                                                       //     592
            $u =& $v[ $k ];                                                                                                          //     593
            unset( $v );                                                                                                             //     594
            $v =& $u;                                                                                                                //     595
            unset( $u );                                                                                                             //     596
        }                                                                                                                            //     597
        return $v;                                                                                                                   //     598
    }                                                                                                                                //     599
                                                                                                                                     //     600
    public function &get_data() {                                                                                                    //     601
        return $this->data;                                                                                                          //     602
    }                                                                                                                                //     603
                                                                                                                                     //     604
}   # class MC_Array_Of_Arrays {                                                                                                     //     605
                                                                                                                                     //     606
class MC_Map_Attributes_To_Variation_Factory {                                                                                       //     607
    private static $maps = [];                                                                                                       //     608
    public static function get( $id, $a ) {                                                                                          //     609
        if ( array_key_exists( $id, self::$maps ) ) {                                                                                //     610
            return self::$maps[ $id ];                                                                                               //     611
        }                                                                                                                            //     612
        return self::$maps[ $id ] = new MC_Array_Of_Arrays( $a );                                                                    //     613
    }                                                                                                                                //     614
}   # class MC_Map_Attributes_To_Variation_Factory {                                                                                 //     615
                                                                                                                                     //     616
class MC_Product_Simple_Variable extends WC_Product_Variable {                                                                       //     617
                                                                                                                                     //     618
    protected $extra_data = [                                                                                                        //     620
        'mc_xii_is_simple_variable' => TRUE                                                                                          //     621
    ];                                                                                                                               //     622
                                                                                                                                     //     623
    public function get_type() {                                                                                                     //     625
        // TODO: use our own type                                                                                                    //     626
        # This means that MC_Product_Simple_Variable uses the same data store as WC_Product_Variable which is currently overridden   //     627
        # from WC_Product_Variable_Data_Store_CPT to MC_Product_Variable_Data_Store_CPT                                              //     628
        return parent::get_type();                                                                                                   //     629
    }                                                                                                                                //     630
                                                                                                                                     //     631

### REDACTED lines  632 ->  666 redacted,     35 lines redacted. ###

                                                                                                                                     //     667
    public function is_purchasable() {                                                                                               //     669
        # $purchasable = parent::is_purchasable();                                                                                   //     670
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $this->get_id() ) ) {                                                 //     674
            return parent::is_purchasable();                                                                                         //     675
        }                                                                                                                            //     676
        return apply_filters( 'woocommerce_is_purchasable',                                                                          //     677
                              $this->exists() && ( 'publish' === $this->get_status()                                                 //     678
                                                   || current_user_can( 'edit_post', $this->get_id() ) )                             //     679
                                              && $this->get_min_max_price()[ 'min' ],                                                //     680
                              $this );                                                                                               //     681
    }   # public function is_purchasable() {                                                                                         //     682
                                                                                                                                     //     683

### REDACTED lines  684 ->  695 redacted,     12 lines redacted. ###

                                                                                                                                     //     696
    public static function sync( $product, $save = true ) {                                                                          //     698
        global $wpdb;                                                                                                                //     700
        if ( ! is_a( $product, 'WC_Product' ) ) {                                                                                    //     702
            $product = wc_get_product( $product );                                                                                   //     703
        }                                                                                                                            //     704
        if ( is_a( $product, 'MC_Product_Simple_Variable' ) ) {                                                                      //     705
            $managed = $wpdb->get_var( $wpdb->prepare( <<<EOD                                                                        //     706
SELECT COUNT(*) FROM $wpdb->posts p, $wpdb->postmeta m, $wpdb->postmeta n                                                            //     707
    WHERE m.post_id = p.ID AND n.post_id = p.ID                                                                                      //     708
        AND p.post_parent = %d                                                                                                       //     709
        AND m.meta_key = '_mc_xii_variation_type' AND m.meta_value = 'base'                                                          //     710
        AND n.meta_key = '_manage_stock' AND n.meta_value = 'yes'                                                                    //     711
EOD                                                                                                                                  //     712
                                                    ,  $product->get_id() ) );                                                       //     713
            if ($managed ) {                                                                                                         //     715
                # $data_store = WC_Data_Store::load( 'product-' . $product->get_type() ); - This will no work as                     //     716
                # MC_Product_Variable_Data_Store_CPT is not subclassed from MC_Product_Data_Store_CPT.                               //     717
                $data_store = WC_Data_Store::load( 'product' );                                                                      //     718
                $data_store->sync_to_no_manage_stock( $product );                                                                    //     719
            }                                                                                                                        //     720
        }                                                                                                                            //     721
        return parent::sync( $product, $save );                                                                                      //     722
    }   # public static function sync( $product, $save = true ) {                                                                    //     723
                                                                                                                                     //     724
    public function get_mc_xii_is_simple_variable( $context = 'view' ) {                                                             //     726
        return $this->get_prop( 'mc_xii_is_simple_variable', $context );                                                             //     727
    }                                                                                                                                //     728
                                                                                                                                     //     729
    public function get_min_max_price() {                                                                                            //     730
        $data_store = $this->get_data_store();                                                                                       //     733
        $prices     = $data_store->get_min_max_prices( $this, [ 'in_stock' ] );                                                      //     734
        $min        = 0;                                                                                                             //     735
        $max        = 0;                                                                                                             //     736
        foreach ( $prices as $attribute => $price ) {                                                                                //     737
            $optional_attribute = MC_Simple_Variation_Functions::is_optional_attribute_obsolete( $attribute );                       //     738
            if ( ! $optional_attribute && ( $price[ 'min' ] === '' || $price[ 'max' ] === '' ) ) {                                   //     739
                $min = $max = '';                                                                                                    //     740
                break;                                                                                                               //     741
            }                                                                                                                        //     742
            $min += $optional_attribute ? 0 : $price[ 'min' ];                                                                       //     743
            if ( $price[ 'max' ] !== '' ) {                                                                                          //     744
                $max += $price[ 'max' ];                                                                                             //     745
            }                                                                                                                        //     746
        }                                                                                                                            //     747
        return [ 'min' => $min, 'max' => $max ];                                                                                     //     748
    }   # public function get_min_max_price() {                                                                                      //     749
                                                                                                                                     //     750
    public function get_number_of_variations() {                                                                                     //     751
        $data_store = $this->get_data_store();                                                                                       //     753
        return $data_store->get_number_of_variations( $this );                                                                       //     754
    }   # public function get_number_of_variations() {                                                                               //     755
                                                                                                                                     //     756
    public function get_all_children( $visible_only = '' ) {                                                                         //     758
        $data_store = $this->get_data_store();                                                                                       //     760
        return $data_store->read_all_children( $this, TRUE )[ 'all' ];                                                               //     761
    }   #   public function get_all_children( $visible_only = '' ) {                                                                 //     762
}   # class MC_Product_Simple_Variable extends WC_Product_Variable {                                                                 //     763
                                                                                                                                     //     764

### REDACTED lines  765 ->  767 redacted,      3 lines redacted. ###

                                                                                                                                     //     768
class MC_Simple_Variation_Functions {                                                                                                //     769
                                                                                                                                     //     770

### REDACTED lines  771 -> 1081 redacted,    311 lines redacted. ###

                                                                                                                                     //    1082
    # init() must run before actions 'init' and 'wp_loaded' are done as init() adds these actions.                                   //    1083
    public static function init() {                                                                                                  //    1084
                                                                                                                                     //    1085

### REDACTED lines 1086 -> 1329 redacted,    244 lines redacted. ###

                                                                                                                                     //    1330
        MC_Utility::add_filter( 'woocommerce_get_children', 'mc_xii_woocommerce_get_children',                                       //    1331
                function( $children, $product, $visible ) {                                                                          //    1332
            if ( $visible ) {                                                                                                        //    1334
                # Remove the base variations from $children.                                                                         //    1335
                $data_store = new MC_Product_Variable_Data_Store_CPT();                                                              //    1336
                $base_ids   = $data_store->get_base_variation_ids( $product );                                                       //    1337
                $children   = array_diff( $children, $base_ids );                                                                    //    1338
            }                                                                                                                        //    1339
            return $children;                                                                                                        //    1340
        }, 10, 3 );   # MC_Utility::add_filter( 'woocommerce_get_children', 'mc_xii_woocommerce_get_children',                       //    1341
                                                                                                                                     //    1342

### REDACTED lines 1343 -> 1652 redacted,    310 lines redacted. ###

                                                                                                                                     //    1653
        MC_Utility::add_action( 'woocommerce_email', 'mc_xii_woocommerce_email', function( $emails ) {                               //    1654
            remove_action( 'woocommerce_low_stock_notification',            [ $emails, 'low_stock' ] );                              //    1655
            remove_action( 'woocommerce_no_stock_notification',             [ $emails, 'no_stock'  ] );                              //    1656
            remove_action( 'woocommerce_product_on_backorder_notification', [ $emails, 'backorder' ] );                              //    1657
            MC_Utility::add_action( 'woocommerce_low_stock_notification', 'mc_xii_woocommerce_low_stock_notification',               //    1658
                    function( $product ) use ( $emails ) {                                                                           //    1659
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1660
                    $emails->low_stock( $product );                                                                                  //    1661
                }                                                                                                                    //    1662
                $amount = get_option( 'woocommerce_notify_low_stock_amount' );                                                       //    1663
                foreach ( self::get_base_variations_of_compound_variations( $product->get_id() ) as $base_id ) {                     //    1664
                    $base_variation = wc_get_product( $base_id );                                                                    //    1665
                    if ( $base_variation->get_stock_quantity() <= $amount ) {                                                        //    1666
                        $emails->low_stock( $base_variation );                                                                       //    1667
                    }                                                                                                                //    1668
                }                                                                                                                    //    1669
            }, 10, 1 );                                                                                                              //    1670
            MC_Utility::add_action( 'woocommerce_no_stock_notification', 'mc_xii_woocommerce_no_stock_notification',                 //    1671
                    function( $product ) use ( $emails ) {                                                                           //    1672
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1673
                    $emails->no_stock( $product );                                                                                   //    1674
                }                                                                                                                    //    1675
                $amount = get_option( 'woocommerce_notify_no_stock_amount' );                                                        //    1676
                foreach ( self::get_base_variations_of_compound_variations( $product->get_id() ) as $base_id ) {                     //    1677
                    $base_variation = wc_get_product( $base_id );                                                                    //    1678
                    if ( $base_variation->get_stock_quantity() <= $amount ) {                                                        //    1679
                        $emails->no_stock( $base_variation );                                                                        //    1680
                    }                                                                                                                //    1681
                }                                                                                                                    //    1682
            }, 10, 1 );                                                                                                              //    1683
                                                                                                                                     //    1684
            MC_Utility::add_action( 'woocommerce_product_on_backorder_notification',                                                 //    1685
                    'mc_xii_woocommerce_product_on_backorder_notification', function( $args ) use ( $emails ) {                      //    1686
                $product = $args['product'];                                                                                         //    1687
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1688
                    $emails->backorder( $args );                                                                                     //    1689
                }                                                                                                                    //    1690
                foreach ( self::get_base_variations_of_compound_variations( $product->get_id() ) as $base_id ) {                     //    1691
                    $base_variation = wc_get_product( $base_id );                                                                    //    1692
                    if ( $base_variation->get_stock_quantity() < 0 ) {                                                               //    1693
                        $args['product'] = $base_variation;                                                                          //    1694
                        $emails->backorder( $args );                                                                                 //    1695
                    }                                                                                                                //    1696
                }                                                                                                                    //    1697
            }, 10, 1 );                                                                                                              //    1698
                                                                                                                                     //    1699
            MC_Utility::add_filter( 'woocommerce_email_content_low_stock', 'mc_xii_woocommerce_email_content_low_stock',             //    1700
                    function( $message, $product ) {                                                                                 //    1701
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1702
                    return $message;                                                                                                 //    1703
                }                                                                                                                    //    1704
                $regex = '!' . sprintf( __( '%1$s is low in stock. There are %2$s left.', 'woocommerce' ), '(.+?)', '(\d+)' ) . '!'; //    1705
                if ( preg_match( $regex, $message, $matches ) === 1 ) {                                                              //    1706
                    if ( ! is_numeric( $matches[1] ) ) {                                                                             //    1707
                        $full_name = $matches[1];                                                                                    //    1708
                        $stock     = $matches[2];                                                                                    //    1709
                    } else {                                                                                                         //    1710
                        $full_name = $matches[2];                                                                                    //    1711
                        $stock     = $matches[1];                                                                                    //    1712
                    }                                                                                                                //    1713
                    if ( ( $end = strpos( $full_name, ')' ) ) !== FALSE ) {                                                          //    1714
                        $name = substr( $full_name, 0, $end + 1 );                                                                   //    1715
                    } else {                                                                                                         //    1716
                        $name = sprintf( '%2$s (%1$s)', '#' . $product->get_id(), $product->get_name() );                            //    1717
                    }                                                                                                                //    1718
                    $message = sprintf( __( '%1$s is low in stock. There are %2$s left.', 'woocommerce' ), $name, $stock );          //    1719
                }                                                                                                                    //    1720
                return $message;                                                                                                     //    1721
            }, 10, 2 );                                                                                                              //    1722
                                                                                                                                     //    1723
            MC_Utility::add_filter( 'woocommerce_email_content_no_stock', 'mc_xii_woocommerce_email_content_no_stock',               //    1724
                    function( $message, $product ) {                                                                                 //    1725
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1726
                    return $message;                                                                                                 //    1727
                }                                                                                                                    //    1728
                $regex = '!' . sprintf( __( '%s is out of stock.', 'woocommerce' ), '(.+?)' ) . '!';                                 //    1729
                if ( preg_match( $regex, $message, $matches ) === 1 ) {                                                              //    1730
                    if ( ( $end = strpos( $matches[1], ')' ) ) !== FALSE ) {                                                         //    1731
                        $name = substr( $matches[1], 0, $end + 1 );                                                                  //    1732
                    }                                                                                                                //    1733
                }                                                                                                                    //    1734
                if ( empty( $name ) ) {                                                                                              //    1735
                    $name = sprintf( '%2$s (%1$s)', '#' . $product->get_id(), $product->get_name() );                                //    1736
                }                                                                                                                    //    1737
                $message = sprintf( __( '%s is out of stock.', 'woocommerce' ), $name );                                             //    1738
                return $message;                                                                                                     //    1739
            }, 10, 2 );                                                                                                              //    1740
                                                                                                                                     //    1741
            MC_Utility::add_filter( 'woocommerce_email_content_backorder', 'mc_xii_woocommerce_email_content_backorder',             //    1742
                    function( $message, $args ) {                                                                                    //    1743
                if ( ! self::is_variation_of_simple_variable( $args['product'] ) ) {                                                 //    1744
                    return $message;                                                                                                 //    1745
                }                                                                                                                    //    1746
                $regex = '!' . sprintf( __( '%1$s units of %2$s have been backordered in order #%3$s.', 'woocommerce' ),             //    1747
                                            '(\d+)', '(.+?)', '(\d+)' ) . '!';                                                       //    1748
                if ( preg_match( $regex, $message, $matches ) === 1 ) {                                                              //    1749
                    for ( $i = 1; $i < count( $matches ); $i++ ) {                                                                   //    1750
                        if ( $matches[i] != $args['quantity'] && $matches[i] != $args['order_id'] ) {                                //    1751
                            if ( ( $end = strpos( $matches[i], ')' ) ) !== FALSE ) {                                                 //    1752
                                $name = substr( $matches[i], 0, $end + 1 );                                                          //    1753
                            }                                                                                                        //    1754
                            break;                                                                                                   //    1755
                        }                                                                                                            //    1756
                    }                                                                                                                //    1757
                }                                                                                                                    //    1758
                if ( empty( $name ) ) {                                                                                              //    1759
                    $name = sprintf( '%2$s (%1$s)', '#' . $args['product']->get_id(), $args['product']->get_name() );                //    1760
                }                                                                                                                    //    1761
                $message = sprintf( __( '%1$s units of %2$s have been backordered in order #%3$s.', 'woocommerce' ),                 //    1762
                                        $args['quantity'], $name, $args['order_id'] );                                               //    1763
                return $message;                                                                                                     //    1764
            }, 10, 2 );                                                                                                              //    1765
        }, 10, 1 );   # MC_Utility::add_action( 'woocommerce_email', 'mc_xii_woocommerce_email', function( $emails ) {               //    1766
                                                                                                                                     //    1767

### REDACTED lines 1768 -> 1788 redacted,     21 lines redacted. ###

                                                                                                                                     //    1789
        MC_Utility::add_action( 'woocommerce_scheduled_sales', 'mc_xii_woocommerce_scheduled_sales', function() {                    //    1790
            # Classic Commerce's wc_scheduled_sales() runs at priority 10 and does not handle simple variations because              //    1792
            # MC_Product_Data_Store_CPT::get_starting_sales() and MC_Product_Data_Store_CPT::get_ending_sales() omits them.          //    1793
            # Do schedule sales for simple variations.                                                                               //    1796
            $data_store = WC_Data_Store::load( 'product' );                                                                          //    1797
            $product_ids = array_merge( $data_store->sv_get_starting_sales(), $data_store->sv_get_ending_sales() );                  //    1798
            self::do_after_product_sales_update( $product_ids );                                                                     //    1799
        }, 11 );   # MC_Utility::add_action( 'woocommerce_scheduled_sales', 'mc_xii_woocommerce_scheduled_sales', function() {       //    1800
                                                                                                                                     //    1801

### REDACTED lines 1802 -> 1859 redacted,     58 lines redacted. ###

                                                                                                                                     //    1860
        add_filter( 'woocommerce_order_item_get_formatted_meta_data',                                                                //    1861
                    'MC_Simple_Variation_Functions::woocommerce_order_item_get_formatted_meta_data', 10, 2 );                        //    1862
                                                                                                                                     //    1863

### REDACTED lines 1864 -> 1900 redacted,     37 lines redacted. ###

                                                                                                                                     //    1901
        if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {                                            //    1902
                                                                                                                                     //    1903
            # Start of actions and filters used by the frontend including AJAX calls.                                                //    1904
                                                                                                                                     //    1905

### REDACTED lines 1906 -> 2207 redacted,    302 lines redacted. ###

                                                                                                                                     //    2208
            MC_Utility::add_action( 'woocommerce_product_additional_information',                                                    //    2209
                                    'mc_xii_woocommerce_product_additional_information_ob_start', function() {                       //    2210
                # Called by single product select options page.                                                                      //    2211
                # We need to process the ouput of wc_display_product_attributes() which runs at priority 10 so turn on output        //    2212
                # buffering at priority 9 and end output buffering and process buffer at priority 11.                                //    2213
                global $product;                                                                                                     //    2214
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) ) {                                                  //    2215
                    return;                                                                                                          //    2216
                }                                                                                                                    //    2217
                ob_start( function( $buffer ) {                                                                                      //    2218
                    $buffer = str_replace( self::UNSELECTED . ', ', '', $buffer );                                                   //    2219
                    $buffer = self::remove_optional_suffix_in_string( $buffer );                                                     //    2220
                    return $buffer;                                                                                                  //    2221
                } );                                                                                                                 //    2222
            }, 9 );                                                                                                                  //    2223
                                                                                                                                     //    2224
            MC_Utility::add_action( 'woocommerce_product_additional_information',                                                    //    2225
                                    'mc_xii_woocommerce_product_additional_information_ob_end', function() {                         //    2226
                global $product;                                                                                                     //    2227
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) ) {                                                  //    2228
                    return;                                                                                                          //    2229
                }                                                                                                                    //    2230
                ob_end_flush();                                                                                                      //    2231
            }, 11 );                                                                                                                 //    2232
                                                                                                                                     //    2233
            # $doing_dropdown_variation_attributes is true when executing code on the product add to cart page                       //    2234
            # - classic-commerce\templates\single-product\add-to-cart\variable.php                                                   //    2235
                                                                                                                                     //    2236
            $doing_dropdown_variation_attributes = FALSE;                                                                            //    2237
            MC_Utility::add_action( 'woocommerce_before_variations_form', 'mc_xii_woocommerce_before_variations_form',               //    2238
                    function() use ( &$doing_dropdown_variation_attributes ) {                                                       //    2239
                global $product;                                                                                                     //    2240
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) ) {                                                  //    2243
                    return;                                                                                                          //    2244
                }                                                                                                                    //    2245
                $doing_dropdown_variation_attributes = TRUE;                                                                         //    2246
                # We will need to process the variations form output so ...                                                          //    2247
                ob_start( function( $buffer ) {                                                                                      //    2248
                    # remove optional suffix                                                                                         //    2249
                    $buffer = preg_replace( '#(<label\s.*?>.*?)' . self::OPTIONAL . '(.*?</label>)#', '$1$2', $buffer );             //    2250
                    $buffer = preg_replace( '#(<option\s.*?>.*?)' . self::OPTIONAL . '(.*?</option>)#', '$1$2', $buffer );           //    2251
                    $buffer = preg_replace_callback( '#<select\s.*?>#', function( $matches ) {                                       //    2252
                        return str_replace( '>', ' autocomplete="off" style="width: 70%;">', $matches[0] );                          //    2253
                    }, $buffer );                                                                                                    //    2254
                    return $buffer;                                                                                                  //    2255
                } );                                                                                                                 //    2256
            } );                                                                                                                     //    2257
                                                                                                                                     //    2258
            MC_Utility::add_action( 'woocommerce_after_variations_form', 'mc_xii_woocommerce_after_variations_form',                 //    2259
                    function() use ( &$doing_dropdown_variation_attributes ) {                                                       //    2260
                global $product;                                                                                                     //    2261
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) || ! $doing_dropdown_variation_attributes ) {        //    2264
                    return;                                                                                                          //    2265
                }                                                                                                                    //    2266
                ob_end_flush();                                                                                                      //    2267
                $doing_dropdown_variation_attributes = FALSE;                                                                        //    2268
            } );                                                                                                                     //    2269
                                                                                                                                     //    2270

### REDACTED lines 2271 -> 2453 redacted,    183 lines redacted. ###

                                                                                                                                     //    2454
            add_filter( 'woocommerce_add_cart_item_data',                                                                            //    2455
                        'MC_Simple_Variation_Functions::woocommerce_add_cart_item_data', 10, 4 );                                    //    2456
                                                                                                                                     //    2457
            add_filter( 'woocommerce_order_again_cart_item_data',                                                                    //    2458
                        'MC_Simple_Variation_Functions::woocommerce_order_again_cart_item_data', 10, 3 );                            //    2459
                                                                                                                                     //    2460

### REDACTED lines 2461 -> 2494 redacted,     34 lines redacted. ###

                                                                                                                                     //    2495
            # End of actions and filters used by the frontend including AJAX calls.                                                  //    2496
                                                                                                                                     //    2497
        }   # if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {                                      //    2498
                                                                                                                                     //    2499
    }   # public static function init() {                                                                                            //    2500
                                                                                                                                     //    2501
    public static function admin_init() {                                                                                            //    2502
                                                                                                                                     //    2503

### REDACTED lines 2504 -> 3257 redacted,    754 lines redacted. ###

                                                                                                                                     //    3258
        if ( $pagenow === 'post.php'  && array_key_exists( 'post', $_GET ) && ! empty( $post = $_GET[ 'post' ] )                     //    3259
                && self::is_simple_variable( $post ) ) {                                                                             //    3260
            MC_Utility::add_action( 'admin_init', 'mc_xii_admin_init', function( $id ) {                                             //    3261
                ob_start( function( $buffer ) {                                                                                      //    3262
                    $buffer = preg_replace( '#(<a\s+class="submitdelete deletion").*>(.+)</a>#',                                     //    3263
                                            '$1 href="' . admin_url( 'admin.php?page=wc-settings&tab=simple_variations' )            //    3264
                                                . '">$2 - please use Settings -> Simple Variations -> Optional Features</a>',        //    3265
                                            $buffer );                                                                               //    3266
                    return $buffer;                                                                                                  //    3267
                } );                                                                                                                 //    3268
            }, 11 );   # Since this code executes as a priority 10 'admin_init' action to install this hook priority 11 is needed.   //    3269
        }   # if ( $pagenow === 'post.php' ) {                                                                                       //    3270
                                                                                                                                     //    3271
        if ( $pagenow === 'post.php' && array_key_exists( 'post', $_GET ) && ! empty( $post = $_GET[ 'post' ] )                      //    3272
                && self::is_simple_variable( $post ) ) {                                                                             //    3273
            # Change 'Attributes' and 'Variations' to 'Component Types' and 'Components'.                                            //    3274
            MC_Utility::add_filter( 'woocommerce_product_data_tabs', 'mc_xii_woocommerce_product_data_tabs', function( $tabs ) {     //    3275
                $tabs[ 'attribute'  ][ 'label' ] = 'Component Types';                                                                //    3277
                $tabs[ 'variations' ][ 'label' ] = 'Components';                                                                     //    3278
                return $tabs;                                                                                                        //    3279
            } );                                                                                                                     //    3280
        }   # if ( $pagenow === 'post.php' && array_key_exists( 'post', $_GET ) && ! empty( $post = $_GET[ 'post' ] )                //    3281
                                                                                                                                     //    3282
    }   # public static function admin_init() {                                                                                      //    3283
                                                                                                                                     //    3284

### REDACTED lines 3285 -> 3406 redacted,    122 lines redacted. ###

                                                                                                                                     //    3407
    public static function is_optional_attribute( $attribute, $product_id = -1, $product = NULL ) {                                  //    3408
        if ( $product instanceof WC_Product ) {                                                                                      //    3410
            $attribute_objects = $product->get_attributes();                                                                         //    3411
            $canonicalized_attribute = substr_compare( $attribute, 'attribute_', 0, 10 ) === 0 ? substr( $attribute, 10 )            //    3414
                                                                                               : $attribute;                         //    3415
            if ( ! empty( $attribute_objects[ $canonicalized_attribute ] ) ) {                                                       //    3416
                $attribute_object = $attribute_objects[ $canonicalized_attribute ];                                                  //    3417
                if ( $attribute_object instanceof MC_Product_Attribute ) {                                                           //    3418
                    return $attribute_object->get_optional();                                                                        //    3421
                }                                                                                                                    //    3422
            }                                                                                                                        //    3423
        }                                                                                                                            //    3424
        return self::is_optional_attribute_obsolete( $attribute );                                                                   //    3426
    }   # public static function is_optional_attribute( $attribute, $product_id = -1, $product = NULL ) {                            //    3427
                                                                                                                                     //    3428
    public static function is_optional_attribute_obsolete( $attribute ) {                                                            //    3429
        return strlen( $attribute ) > self::$optional_suffix_length                                                                  //    3430
            && ! substr_compare( $attribute, self::OPTIONAL, - self::$optional_suffix_length );                                      //    3431
    }                                                                                                                                //    3432
                                                                                                                                     //    3433
    public static function remove_optional_suffix( $attribute_name ) {                                                               //    3434
        return substr( $attribute_name, 0, - self::$optional_suffix_length );                                                        //    3435
    }                                                                                                                                //    3436
                                                                                                                                     //    3437
    public static function remove_optional_suffix_in_string( $string ) {                                                             //    3438
        return str_replace( self::OPTIONAL, '', $string );                                                                           //    3439
    }                                                                                                                                //    3440
                                                                                                                                     //    3441
    # N.B. 'is_optional' is not a key in the database post_meta field '_product_attributes' rather in the database the optional      //    3442
    #      attributes have a self::OPTIONAL suffix.                                                                                  //    3443
    # php wp-cli.phar eval 'print_r(get_post_meta(45,"_product_attributes"));'                                                       //    3444
    public static function prepare_request_product_attributes( &$data, $product_id ) {                                               //    3445
        # prepare attributes for call to WC_Meta_Box_Product_Data::prepare_attributes()                                              //    3446
        # insert unselected attribute value and insert missing visibility and variation attributes                                   //    3447
        if ( ! empty( $data['attribute_names'] ) ) {                                                                                 //    3448
            foreach ( $data['attribute_names'] as $index => &$name ) {                                                               //    3449
                if ( ! $name ) {                                                                                                     //    3450
                    continue;                                                                                                        //    3451
                }                                                                                                                    //    3452
                if ( ! empty( $data['attribute_optional'][ $index ] ) ) {                                                            //    3453
                    # optional component so add optional suffix to attribute slug                                                    //    3454
                    if ( ! self::is_optional_attribute_obsolete( $name ) ) {                                                         //    3455
                        $name .= self::OPTIONAL;                                                                                     //    3456
                    }                                                                                                                //    3457
                } else {                                                                                                             //    3458
                    # not optional so remove optional suffix from attribute slug if it exists                                        //    3459
                    if ( self::is_optional_attribute_obsolete( $name ) ) {                                                           //    3460
                        $name = substr( $name, 0, - $suffix_len );                                                                   //    3461
                    }                                                                                                                //    3462
                }                                                                                                                    //    3463
            }                                                                                                                        //    3464
        }                                                                                                                            //    3465
        # remove my for_simple_variation attribute                                                                                   //    3466
        unset( $data['attribute_for_simple_variation'], $data['attribute_optional'] );                                               //    3467
    }   # private static function prepare_request_product_attributes( &$data ) {                                                     //    3468
                                                                                                                                     //    3469

### REDACTED lines 3470 -> 4084 redacted,    615 lines redacted. ###

                                                                                                                                     //    4085
    private static function do_after_product_sales_update( $product_ids ) {                                                          //    4086
        error_log( 'do_after_product_sales_update():current_filter() = ' . current_filter() );                                       //    4087
        $time_stamp = current_time( 'timestamp', TRUE );                                                                             //    4088
        $base_ids   = [];                                                                                                            //    4089
        foreach ( $product_ids as $id ) {                                                                                            //    4090
            if ( self::is_base_variation( $id ) ) {                                                                                  //    4091
                update_post_meta( $id, '_mc_xii_sales_data_synced_at', $time_stamp );                                                //    4092
                $base_ids[] = $id;                                                                                                   //    4093
            }                                                                                                                        //    4094
        }                                                                                                                            //    4095
        $stale_compound_variations_ids = self::get_stale_compound_variations_of_base_variations_wrt_sales( $base_ids, $time_stamp ); //    4096
        $not_completed = FALSE;                                                                                                      //    4097
        foreach( $stale_compound_variations_ids as $id ) {                                                                           //    4098
            self::calculate_sale_data_of_compound_variations_from_their_base_variations( $id, NULL, TRUE );                          //    4099
            # Since this PHP execution session is shared do not use more than half of the available execution time.                  //    4100
            if ( MC_Execution_Time::near_max_execution_limit( 0.5 ) ) {                                                              //    4101
                $not_completed = TRUE;                                                                                               //    4102
                break;                                                                                                               //    4103
            }                                                                                                                        //    4104
        }                                                                                                                            //    4105
        if ( $not_completed ) {                                                                                                      //    4106
            wp_schedule_single_event( time(), 'woocommerce_scheduled_sales', [ 'unique' => time() ] );                               //    4107
            $url  = site_url( 'wp-cron.php' ) . '?' . $_SERVER['QUERY_STRING'];                                                      //    4108
            $args = [                                                                                                                //    4109
                'timeout'   => 0.01,                                                                                                 //    4110
                'blocking'  => false,                                                                                                //    4111
                'sslverify' => apply_filters( 'https_local_ssl_verify', false )                                                      //    4112
            ];                                                                                                                       //    4113
            wp_remote_post( $url, $args );                                                                                           //    4114
        } else {                                                                                                                     //    4115
            # Since we are all done it is ok to clear the relevant _sale_price_dates_(from|to) of the relevant base variations.      //    4116
            # However, wc_scheduled_sales() should have already handle this.                                                         //    4117
        }                                                                                                                            //    4118
    }   # private static function do_after_product_sales_update( $product_ids ) {                                                    //    4119
                                                                                                                                     //    4120
    private static function get_stale_compound_variations_of_base_variations_wrt_sales( $base_ids, $time_stamp ) {                   //    4121
        $type_compound = self::TYPE_COMPOUND;                                                                                        //    4122
        $compound_ids = [];                                                                                                          //    4123
        foreach ( $base_ids as $base_id ) {                                                                                          //    4124
            # Get stale compound variations that use these base variations.                                                          //    4125
            $ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                            //    4126
SELECT m.post_id FROM $wpdb->postmeta m                                                                                              //    4127
    JOIN $wpdb->postmeta as postmeta_type    ON m.post_id = postmeta_type.post_id                                                    //    4128
    JOIN $wpdb->postmeta as postmeta_sync_at ON m.post_id = postmeta_sync_at.post_id                                                 //    4129
WHERE postmeta_type.meta_key = '_mc_xii_variation_type' AND postmeta_type.meta_value = '$type_compound'                              //    4130
    AND postmeta_sync_at.meta_key = '_mc_xii_sales_data_synced_at' AND postmeta_sync_at.meta_value < %d                              //    4131
    AND m.meta_key = '_mc_xii_base_variations' AND m.meta_value LIKE '%%<%d>%%'                                                      //    4132
EOD                                                                                                                                  //    4133
                                                   , $base_id, $time_stamp ) );                                                      //    4134
            $compound_ids += $ids;                                                                                                   //    4135
        }                                                                                                                            //    4136
        return array_unique( $compound_ids );                                                                                        //    4137
    }   # private static function get_stale_compound_variations_of_base_variations( $base_ids, $time_stamp ) {                       //    4138
                                                                                                                                     //    4139
    # Update the sales data of compound variation of a simple variable product from the sales data of its base variations.           //    4140
    # N.B. After applying the sale price wc_scheduled_sales() changes the meta_value of '_sale_price_dates_from' to ''.              //    4141
    # N.B. calculate_sale_data_of_compound_variations_from_their_base_variations() does not use the meta_value '_price' of base      //    4142
    #      variations, i.e., it does not matter if the base variations have been updated with the current sale price so it can be    //    4143
    #      called before or after wc_scheduled_sales().                                                                              //    4144
    # N.B. calculate_sale_data_of_compound_variations_from_their_base_variations() does not modify the base variations in anyway.    //    4145
    # N.B. The sales data of virtual compound variations is handled by MC_Product_Variation_Data_Store_CPT::read().                  //    4146
    # Since, it is inexpensive to do also update stock. This may be useful if we choose to run                                       //    4147
    # calculate_sale_data_of_compound_variations_from_their_base_variations() before a product is displayed in the frontend.         //    4148
    # Then the correct stock can be displayed even if sync_compound_variations_with_base_variations() has not completed.             //    4149
    private static function calculate_sale_data_of_compound_variations_from_their_base_variations( $product, $bases = NULL,          //    4150
                                                                                                   $update_database = TRUE ) {       //    4151
        # error_log( 'calculate_sale_data_of_compound_variations_from_their_base_variations():current_filter() = '                   //    4152
        #            . current_filter() );                                                                                           //    4153
        # error_log( 'calculate_sale_data_of_compound_variations_from_their_base_variations():BACKTRACE = '                          //    4154
        #            . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true) );                                             //    4155
        MC_Filter_Recorder::record( 'calculate_sale_data_of_compound_variations_from_their_base_variations' );                       //    4156
        global $wpdb;                                                                                                                //    4157
        if (       doing_action( 'woocommerce_payment_complete' )        || doing_action( 'woocommerce_order_status_completed' )     //    4158
                || doing_action( 'woocommerce_order_status_processing' ) || doing_action( 'woocommerce_order_status_on-hold' )       //    4159
                || doing_action( 'woocommerce_order_status_refunded' )   || doing_action( 'woocommerce_order_status_cancelled' ) ) { //    4160
            # The product is being updated and is not stable and continuing would give incorrect results.                            //    4161
            return;                                                                                                                  //    4162
        }                                                                                                                            //    4163
        # The sales to/from date of compound variations must be dynamically computed from the sales to/from date of its base         //    4164
        # variations.                                                                                                                //    4165
        $meta_keys          = [ '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to', '_stock' ];       //    4166
        $meta_keys_count    = count( $meta_keys );                                                                                   //    4167
        $meta_keys_imploded = '"' . implode( '", "', $meta_keys ) . '"';                                                             //    4168
        if ( ! $bases ) {                                                                                                            //    4169
            $base_ids = self::get_base_variations( $product );                                                                       //    4170
            if ( ! $base_ids || count( $base_ids ) <= 1 ) {                                                                          //    4171
                return;                                                                                                              //    4172
            }                                                                                                                        //    4173
            $base_ids_count = count( $base_ids );                                                                                    //    4174
            $base_ids       = implode( ', ', $base_ids );                                                                            //    4175
            $results        = $wpdb->get_results( <<<EOD                                                                             //    4176
SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE post_id IN ( $base_ids ) AND meta_key IN ( $meta_keys_imploded )     //    4177
EOD                                                                                                                                  //    4178
            );                                                                                                                       //    4179
            $bases = [];                                                                                                             //    4180
            foreach ( $results as $result ) {                                                                                        //    4181
                $post_id = $result->post_id;                                                                                         //    4182
                if ( ! array_key_exists( $post_id, $bases ) ) {                                                                      //    4183
                    $bases[ $post_id ] = [];                                                                                         //    4184
                }                                                                                                                    //    4185
                $bases[ $post_id ][ substr( $result->meta_key, 1 ) ] = $result->meta_value;                                          //    4186
            }                                                                                                                        //    4187
            if ( count( $bases ) != $base_ids_count ) {                                                                              //    4188
                error_log( 'count( $bases ) != $base_ids_count' );                                                                   //    4189
                error_log( '$bases = ' . print_r( $bases, true ) );                                                                  //    4190
                return;                                                                                                              //    4191
            }                                                                                                                        //    4192
        }                                                                                                                            //    4193
        foreach ( $bases as $post_id => &$base ) {                                                                                   //    4194
            $missing = count( $base ) < $meta_keys_count;                                                                            //    4195
            $base = (object) $base;                                                                                                  //    4196
            if ( $missing ) {                                                                                                        //    4197
                foreach ( $meta_keys as $meta_key ) {                                                                                //    4198
                    if ( ! property_exists( $base, substr( $meta_key, 1 ) ) ) {                                                      //    4199
                    }                                                                                                                //    4203
                }                                                                                                                    //    4204
            }                                                                                                                        //    4205
        }                                                                                                                            //    4206
        unset( $base );                                                                                                              //    4207
        $now                 = (new DateTime())->getTimestamp();                                                                     //    4208
        $update_database     = TRUE;                                                                                                 //    4209
        $regular_price       = 0;                                                                                                    //    4210
        $sale_price          = 0;                                                                                                    //    4211
        $from_sale_date      = NULL;                                                                                                 //    4212
        $to_sale_date        = NULL;                                                                                                 //    4213
        $next_from_sale_date = NULL;                                                                                                 //    4214
        $stock               = PHP_INT_MAX;                                                                                          //    4215
        foreach ( $bases as $base_id => $base ) {                                                                                    //    4216
            if ( is_numeric( $stock ) ) {                                                                                            //    4217
                if ( property_exists( $base, 'stock' ) && is_numeric( $base->stock ) ) {                                             //    4218
                    if ( $base->stock < $stock ) {                                                                                   //    4219
                        $stock = $base->stock;                                                                                       //    4220
                    }                                                                                                                //    4221
                } else {                                                                                                             //    4222
                    $stock = '';                                                                                                     //    4223
                }                                                                                                                    //    4224
            }                                                                                                                        //    4225
            $sale_price_dates_from = $base->sale_price_dates_from;                                                                   //    4226
            $sale_price_dates_to   = $base->sale_price_dates_to;                                                                     //    4227
            if ( ! $sale_price_dates_from ) {                                                                                        //    4228
                $sale_price_dates_from = 0;                                                                                          //    4229
            }                                                                                                                        //    4230
            if ( ! $sale_price_dates_to ) {                                                                                          //    4231
                $sale_price_dates_to = PHP_INT_MAX;                                                                                  //    4232
            }                                                                                                                        //    4233
            if ( ! is_numeric( $base->sale_price ) || $sale_price_dates_to < $now ) {                                                //    4234
                if ( is_numeric( $base->regular_price ) ) {                                                                          //    4235
                    if ( is_numeric( $sale_price ) ) {                                                                               //    4236
                        $sale_price    += $base->regular_price;                                                                      //    4237
                    }                                                                                                                //    4238
                    if ( is_numeric ( $regular_price ) ) {                                                                           //    4239
                        $regular_price += $base->regular_price;                                                                      //    4240
                    }                                                                                                                //    4241
                } else {                                                                                                             //    4242
                    $sale_price     = '';                                                                                            //    4243
                    $regular_price  = '';                                                                                            //    4244
                }                                                                                                                    //    4245
                continue;                                                                                                            //    4246
            }                                                                                                                        //    4247
            if ( $sale_price_dates_from < $now ) {                                                                                   //    4248
                $sale_price_dates_from = 0;                                                                                          //    4249
            }                                                                                                                        //    4250
            if ( is_numeric( $base->sale_price ) && ( $from_sale_date === NULL || $sale_price_dates_from < $from_sale_date ) ) {     //    4251
                $from_sale_date = $sale_price_dates_from;                                                                            //    4252
                $to_sale_date   = $sale_price_dates_to;                                                                              //    4253
                $sale_price     = $regular_price + $base->sale_price;                                                                //    4254
            } else if ( is_numeric( $base->sale_price ) && $sale_price_dates_from === $from_sale_date ) {                            //    4255
                if ( is_numeric( $sale_price ) ) {                                                                                   //    4256
                    $sale_price += $base->sale_price;                                                                                //    4257
                }                                                                                                                    //    4258
                if ( $sale_price_dates_to < $to_sale_date ) {                                                                        //    4259
                    $to_sale_date = $sale_price_dates_to;                                                                            //    4260
                }                                                                                                                    //    4261
            } else {                                                                                                                 //    4262
                if ( is_numeric( $base->regular_price ) ) {                                                                          //    4263
                    if ( is_numeric( $sale_price ) ) {                                                                               //    4264
                        $sale_price += $base->regular_price;                                                                         //    4265
                    }                                                                                                                //    4266
                } else {                                                                                                             //    4267
                    $sale_price = '';                                                                                                //    4268
                }                                                                                                                    //    4269
                if ( is_numeric( $base->sale_price ) && ( $next_from_sale_date === NULL                                              //    4270
                        || $sale_price_dates_from < $next_from_sale_date ) ) {                                                       //    4271
                    $next_from_sale_date = $sale_price_dates_from;                                                                   //    4272
                }                                                                                                                    //    4273
            }                                                                                                                        //    4274
            if ( is_numeric( $base->regular_price ) ) {                                                                              //    4275
                if ( is_numeric ( $regular_price ) ) {                                                                               //    4276
                    $regular_price += $base->regular_price;                                                                          //    4277
                }                                                                                                                    //    4278
            } else {                                                                                                                 //    4279
                $regular_price = '';                                                                                                 //    4280
            }                                                                                                                        //    4281
        }   # foreach ( $bases as $base ) {                                                                                          //    4282
        if ( $next_from_sale_date !== NULL && $next_from_sale_date < $to_sale_date ) {                                               //    4283
            $to_sale_date = $next_from_sale_date;                                                                                    //    4284
        }                                                                                                                            //    4285
        if ( $from_sale_date === 0 ) {                                                                                               //    4286
            $from_sale_date = NULL;                                                                                                  //    4287
        }                                                                                                                            //    4288
        if ( $to_sale_date === PHP_INT_MAX ) {                                                                                       //    4289
            $to_sale_date = NULL;                                                                                                    //    4290
        }                                                                                                                            //    4291
        $product->set_sale_price(        $sale_price     );                                                                          //    4292
        $product->set_date_on_sale_from( $from_sale_date );                                                                          //    4293
        $product->set_date_on_sale_to(   $to_sale_date   );                                                                          //    4294
        if ( $update_database ) {                                                                                                    //    4295
            update_post_meta( $product->get_id(), '_regular_price',         $regular_price                                      );   //    4296
            update_post_meta( $product->get_id(), '_sale_price',            $sale_price < $regular_price ? $sale_price     : '' );   //    4297
            update_post_meta( $product->get_id(), '_sale_price_dates_from', $from_sale_date              ? $from_sale_date : '' );   //    4298
            update_post_meta( $product->get_id(), '_sale_price_dates_to',   $to_sale_date                ? $to_sale_date   : '' );   //    4299
            update_post_meta( $product->get_id(), '_stock',                 $stock                                              );   //    4300
        }                                                                                                                            //    4301
        $is_on_sale = $from_sale_date <= $now && $sale_price < $regular_price;                                                       //    4302
        $price = $is_on_sale ? $sale_price : $regular_price;                                                                         //    4303
        if ( $update_database ) {                                                                                                    //    4304
            update_post_meta( $product->get_id(), '_price',                       $price );                                          //    4305
            update_post_meta( $product->get_id(), '_mc_xii_sales_data_synced_at', current_time( 'timestamp', TRUE ) );               //    4306
        }                                                                                                                            //    4307
        $product->set_price( $price );                                                                                               //    4308
        return $is_on_sale;                                                                                                          //    4309
    }   # private static function calculate_sale_data_of_compound_variations_from_their_base_variations( $product, $bases = NULL,    //    4310
                                                                                                                                     //    4311

### REDACTED lines 4312 -> 5081 redacted,    770 lines redacted. ###

                                                                                                                                     //    5082
    public static function woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {                //    5083
        $extras = [];                                                                                                                //    5084
        if ( doing_action( 'wp_loaded' ) && ! empty( $_REQUEST['add-to-cart'] ) && is_numeric( $_REQUEST['add-to-cart'] ) ) {        //    5085
            # Called from inside WC_Form_Handler::add_to_cart_action().                                                              //    5086
            if ( MC_Simple_Variation_Functions::is_simple_variable( $product_id ) ) {                                                //    5087
                foreach ( $_REQUEST as $key => $value ) {                                                                            //    5088
                    if ( strpos( $key, 'attribute_' ) === 0 ) {                                                                      //    5089
                        # WC_Order_Item::get_formatted_meta_data() calls wc_is_attribute_in_product_name() and removes meta data     //    5090
                        # if its value is in the product name so modify the meta value                                               //    5091
                        $extras[ self::SIMPLE_VARIATION_DATA_PREFIX . substr( $key, 10 ) ] = '@#' . $value;                          //    5092
                    }                                                                                                                //    5093
                }                                                                                                                    //    5094
            }                                                                                                                        //    5095
        } else if ( doing_action( 'wp_ajax_woocommerce_add_to_cart' ) || doing_action( 'wp_ajax_nopriv_woocommerce_add_to_cart' )    //    5096
                || doing_action( 'wc_ajax_add_to_cart' ) ) {                                                                         //    5097
            # Called from inside WC_AJAX::add_to_cart().                                                                             //    5098
            if ( $variation_id ) {                                                                                                   //    5099
                if ( $variation = wc_get_product( $variation_id ) ) {                                                                //    5100
                    if ( MC_Simple_Variation_Functions::is_simple_variable( $variation->get_parent_id() ) ) {                        //    5101
                        foreach ( wc_get_product( $variation_id )->get_variation_attributes() as $attribute ) {                      //    5102
                            // TODO: $extras[] =                                                                                     //    5103
                        }                                                                                                            //    5104
                    }                                                                                                                //    5105
                }                                                                                                                    //    5106
            }                                                                                                                        //    5107
        }                                                                                                                            //    5108
        return array_merge( $cart_item_data, $extras );                                                                              //    5109
    }   # public static function woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {          //    5110
                                                                                                                                     //    5111
    # When a cart is created from an order our meta data in the order items is not automatically copied but the filter               //    5112
    # 'woocommerce_order_again_cart_item_data' can be used to copy our meta data from the order items into the cart items. See       //    5113
    # WC_Cart_Session::populate_cart_from_order().                                                                                   //    5114
    public static function woocommerce_order_again_cart_item_data( $cart_item_data, $item, $order ) {                                //    5115
        $meta_data = $item->get_meta_data();                                                                                         //    5116
        $extras = [];                                                                                                                //    5117
        foreach ( $meta_data as $meta ) {                                                                                            //    5118
            if ( substr_compare( $meta->key, self::SIMPLE_VARIATION_DATA_PREFIX, 0, 7 ) === 0 ) {                                    //    5119
                $extras[ $meta->key ] = $meta->value;                                                                                //    5120
            }                                                                                                                        //    5121
        }                                                                                                                            //    5122
        return array_merge( $cart_item_data, $extras );                                                                              //    5123
    }   # public static function woocommerce_order_again_cart_item_data( $cart_item_data, $item, $order ) {                          //    5124
                                                                                                                                     //    5125
    public static function woocommerce_add_cart_item( $cart_item_data, $key, $alt_key = NULL ) {                                     //    5126
        if ( $alt_key !== NULL ) {                                                                                                   //    5127
            $key = $alt_key;                                                                                                         //    5128
        }                                                                                                                            //    5129
        $product = $cart_item_data[ 'data' ];                                                                                        //    5130
        if ( self::is_variation_of_simple_variable( $product ) ) {                                                                   //    5131
            foreach ( $cart_item_data as $item_key => $item_value ) {                                                                //    5132
                if ( substr_compare( $item_key, self::SIMPLE_VARIATION_DATA_PREFIX, 0, 7 ) === 0 ) {                                 //    5133
                    $product->add_meta_data( $item_key, $item_value, TRUE );                                                         //    5134
                }                                                                                                                    //    5135
            }                                                                                                                        //    5136
        }                                                                                                                            //    5137
        $product->add_meta_data( MC_Product_Variation::CART_ITEM_KEY, $key, TRUE );                                                  //    5138
        return $cart_item_data;                                                                                                      //    5140
    }   # public static function woocommerce_add_cart_item( $cart_item_data ) {                                                      //    5141
                                                                                                                                     //    5142
    public static function woocommerce_order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {                          //    5143
        foreach ( $formatted_meta as $meta ) {                                                                                       //    5144
            $meta->display_key   = str_replace( self::SIMPLE_VARIATION_DATA_PREFIX, '', $meta->display_key );                        //    5145
            $meta->display_value = str_replace( [ '@#', self::UNSELECTED ], [ '', self::$none_label ], $meta->display_value );       //    5146
        }                                                                                                                            //    5147
        return $formatted_meta;                                                                                                      //    5148
    }   # public static function woocommerce_order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {                    //    5149
                                                                                                                                     //    5150

### REDACTED lines 5151 -> 5423 redacted,    273 lines redacted. ###

                                                                                                                                     //    5424
}   # class MC_Simple_Variation_Functions {                                                                                          //    5425
                                                                                                                                     //    5426
MC_Simple_Variation_Functions::init();                                                                                               //    5427
                                                                                                                                     //    5428
add_action( 'admin_init', function() {                                                                                               //    5429
    MC_Simple_Variation_Functions::admin_init();                                                                                     //    5430
} );                                                                                                                                 //    5431
                                                                                                                                     //    5432

### REDACTED lines 5433 -> 5524 redacted,     92 lines redacted. ###

                                                                                                                                     //    5525
class MC_Product_Attribute extends WC_Product_Attribute {                                                                            //    5526
                                                                                                                                     //    5527
    public static function init() {                                                                                                  //    5528
        add_filter( 'woocommerce_product_attribute_class',                                                                           //    5529
                    'MC_Product_Attribute::woocommerce_product_attribute_class', 10, 2 );                                            //    5530
    }                                                                                                                                //    5531
                                                                                                                                     //    5532
    public static function admin_init() {                                                                                            //    5533
        add_filter( 'woocommerce_admin_meta_boxes_prepare_attribute',                                                                //    5534
                    'MC_Product_Attribute::woocommerce_admin_meta_boxes_prepare_attribute', 10, 3 );                                 //    5535
        add_action( 'woocommerce_after_product_attribute_settings',                                                                  //    5536
                    'MC_Product_Attribute::woocommerce_after_product_attribute_settings', 10, 2 );                                   //    5537
    }                                                                                                                                //    5538
                                                                                                                                     //    5539
    public static function woocommerce_product_attribute_class( $classname, $data_or_product ) {                                     //    5540
        if ( $data_or_product instanceof WC_Product ) {                                                                              //    5541
            $product = $data_or_product;                                                                                             //    5542
        } else {                                                                                                                     //    5544
            $data = $data_or_product;                                                                                                //    5545
        }                                                                                                                            //    5547
        return $classname;                                                                                                           //    5548
    }                                                                                                                                //    5549
                                                                                                                                     //    5550
    // TODO: woocommerce_admin_meta_boxes_prepare_attribute() has support for backward compatibility with                            //    5551
    // TODO: MC_Simple_Variation_Functions::prepare_request_product_attributes() - remove that support after                         //    5552
    // TODO: MC_Product_simple_Variation::prepare_request_product_attributes() is removed.                                           //    5553
    public static function woocommerce_admin_meta_boxes_prepare_attribute( $attribute, $data, $i ) {                                 //    5554
        if ( ! empty( $_POST[ 'product-type' ] ) && $_POST[ 'product-type' ] !== 'simple-variable' ) {                               //    5557
            return $attribute;                                                                                                       //    5558
        }                                                                                                                            //    5559
        $post_id = (integer) ( ! empty( $_POST[ 'post_ID' ] ) ? $_POST[ 'post_ID' ]                                                  //    5560
                                                              : ( ! empty( $_POST[ 'post_id' ] ) ? $_POST[ 'post_id' ] : 0 ) );      //    5561
        if ( ! MC_Simple_Variation_Functions::is_simple_variable( $post_id ) ) {                                                     //    5563
            return $attribute;                                                                                                       //    5564
        }                                                                                                                            //    5565
        $options = $attribute->get_options();                                                                                        //    5566
        $new_attribute = new MC_Product_Attribute( $attribute );                                                                     //    5568
        $new_attribute->set_visible( 1 );                                                                                            //    5569
        $new_attribute->set_variation( 1 );                                                                                          //    5570
        if ( ! in_array( MC_Simple_Variation_Functions::UNSELECTED, $options ) ) {                                                   //    5571
            array_unshift( $options, MC_Simple_Variation_Functions::UNSELECTED );                                                    //    5572
            $new_attribute->set_options( $options );                                                                                 //    5573
        }                                                                                                                            //    5574
        $new_attribute->set_optional( ! empty( $data[ 'attribute_optional' ][ $i ] ) ? 1 : 0 );                                      //    5575
        // TODO: Below is hack for backward compatibility.                                                                           //    5576
        $new_attribute->set_optional(                                                                                                //    5577
                MC_Simple_Variation_Functions::is_optional_attribute_obsolete( $data[ 'attribute_names' ][ $i ] ) ? 1 : 0 );         //    5578
        return $new_attribute;                                                                                                       //    5580
    }   # public static function woocommerce_admin_meta_boxes_prepare_attribute( $attribute, $data, $i ) {                           //    5581
                                                                                                                                     //    5582
    public static function woocommerce_after_product_attribute_settings( $attribute, $i ) {                                          //    5583
        $checked = $attribute instanceof MC_Product_Attribute ? $attribute->get_optional() : FALSE;                                  //    5586
?>                                                                                                                                   <!--  5587 -->
<tr class="mc_xii-attribute_optional">                                                                                               <!--  5588 -->
    <td>                                                                                                                             <!--  5589 -->
        <div class="enable_optional enable_variation show_if_simple-variable">                                                       <!--  5590 -->
            <label>                                                                                                                  <!--  5591 -->
                <input type="checkbox" class="checkbox" <?php checked( $checked, true ); ?>                                          <!--  5592 -->
                        name="attribute_optional[<?php echo esc_attr( $i ); ?>]" value="1" />                                        <!--  5593 -->
                <?php echo esc_html( ucfirst( MC_Simple_Variation_Functions::$optional_label ) ); ?>                                 <!--  5594 -->
            </label>                                                                                                                 <!--  5595 -->
        </div>                                                                                                                       <!--  5596 -->
    </td>                                                                                                                            <!--  5597 -->
</tr>                                                                                                                                <!--  5598 -->
<?php                                                                                                                                //    5599
    }   # public static function woocommerce_after_product_attribute_settings( $attribute, $i ) {                                    //    5600
                                                                                                                                     //    5601
    public function __construct( $attribute ) {                                                                                      //    5602
        $this->set_id(        $attribute->get_id()        );                                                                         //    5603
        $this->set_name(      $attribute->get_name()      );                                                                         //    5604
        $this->set_options(   $attribute->get_options()   );                                                                         //    5605
        $this->set_position(  $attribute->get_position()  );                                                                         //    5606
        $this->set_visible(   $attribute->get_visible()   );                                                                         //    5607
        $this->set_variation( $attribute->get_variation() );                                                                         //    5608
        $this->set_optional(  0 );                                                                                                   //    5609
    }   # public function __construct( $attribute ) {                                                                                //    5610
                                                                                                                                     //    5611
    public function set_optional( $value ) {                                                                                         //    5612
        $this->data[ 'optional' ] = wc_string_to_bool( $value );                                                                     //    5613
    }                                                                                                                                //    5614
                                                                                                                                     //    5615
    public function get_optional() {                                                                                                 //    5616
        return $this->data[ 'optional' ];                                                                                            //    5617
    }                                                                                                                                //    5618
                                                                                                                                     //    5619
    public function get_data() {                                                                                                     //    5621
        return array_merge( parent::get_data(), [ 'is_optional' => $this->get_optional() ? 1 : 0 ] );                                //    5622
    }                                                                                                                                //    5623
                                                                                                                                     //    5624
    # The MC_Product_Attribute object is only available after instantiating its owner MC_Product_Simple_Variable object. This can be //    5625
    # very expensive when scanning all products. is_optional_attribute_from_database() directly reads the database to determine if   //    5626
    # an attribute is optional to do this more efficiently.                                                                          //    5627
    public static function is_optional_attribute_from_database( $attribute, $product_id ) {                                          //    5628
        $canonicalized_attribute = substr_compare( $attribute, 'attribute_', 0, 10 ) === 0 ? substr( $attribute, 10 )                //    5629
                                                                                           : $attribute;                             //    5630
        $attributes = get_post_meta( $product_id, '_product_attributes', TRUE );                                                     //    5631
        if ( is_array( $attributes ) && array_key_exists( $canonicalized_attribute, $attributes )                                    //    5632
                && array_key_exists( 'is_optional', $attributes[ $canonicalized_attribute ] ) ) {                                    //    5633
            return (boolean) $attributes[ $canonicalized_attribute ][ 'is_optional' ];                                               //    5634
        }                                                                                                                            //    5635
        return FALSE;                                                                                                                //    5636
    }   # public static function is_optional_attribute_from_database( $attribute, $product_id ) {                                    //    5637
                                                                                                                                     //    5638
}   # class MC_Product_Attribute extends WC_Product_Attribute {                                                                      //    5639
                                                                                                                                     //    5640
add_action( 'init', function() {                                                                                                     //    5641
    MC_Product_Attribute::init();                                                                                                    //    5642
} );                                                                                                                                 //    5643
                                                                                                                                     //    5644
add_action( 'admin_init', function() {                                                                                               //    5645
    MC_Product_Attribute::admin_init();                                                                                              //    5646
} );                                                                                                                                 //    5647
                                                                                                                                     //    5648
class MC_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT {                                                                  //    5649
                                                                                                                                     //    5650

### REDACTED lines 5651 -> 5658 redacted,      8 lines redacted. ###

                                                                                                                                     //    5659
    public static function init() {                                                                                                  //    5660
        # filter 'woocommerce_product_data_store' is applied in WC_Data_Store::__construct()                                         //    5661
        MC_Utility::add_filter( 'woocommerce_product_data_store', 'mc_xii_woocommerce_product_data_store',                           //    5662
                function( $store ) {                                                                                                 //    5663
            return new MC_Product_Data_Store_CPT();                                                                                  //    5664
        } );                                                                                                                         //    5665
    }                                                                                                                                //    5666
                                                                                                                                     //    5667

### REDACTED lines 5668 -> 5944 redacted,    277 lines redacted. ###

                                                                                                                                     //    5945
    # Classic Commerce's wc_scheduled_sales() should not process Simple Variation products so omit them from get_starting_sales()    //    5946
                                                                                                                                     //    5947
    public function get_starting_sales() {                                                                                           //    5949
        global $wpdb;                                                                                                                //    5950
        return $this->_get_starting_sales( "AND NOT EXISTS ( SELECT * FROM {$wpdb->postmeta} as postmeta_type                        //    5951
                                                WHERE postmeta_type.post_id = postmeta.post_id                                       //    5952
                                                    AND postmeta_type.meta_key = '_mc_xii_variation_type' )" );                      //    5953
    }   # public function get_starting_sales() {                                                                                     //    5954
                                                                                                                                     //    5955
    # Classic Commerce's wc_scheduled_sales() should not process Simple Variation products so omit them from get_ending_sales()      //    5956
                                                                                                                                     //    5957
    public function get_ending_sales() {                                                                                             //    5959
        global $wpdb;                                                                                                                //    5960
        return $this->_get_ending_sales( "AND NOT EXISTS ( SELECT * FROM {$wpdb->postmeta} as postmeta_type                          //    5961
                                              WHERE postmeta_type.post_id = postmeta.post_id                                         //    5962
                                                  AND postmeta_type.meta_key = '_mc_xii_variation_type' )" );                        //    5963
    }   # public function get_ending_sales() {                                                                                       //    5964
                                                                                                                                     //    5965
    # sv_get_starting_sales() gets base variations where sales are beginning                                                         //    5966
                                                                                                                                     //    5967
    public function sv_get_starting_sales() {                                                                                        //    5968
        global $wpdb;                                                                                                                //    5969
        $type_base = MC_Simple_Variation_Functions::TYPE_BASE;                                                                       //    5970
        return $this->_get_starting_sales( "AND EXISTS ( SELECT * FROM {$wpdb->postmeta} as postmeta_type                            //    5971
                                                WHERE postmeta_type.post_id = postmeta.post_id                                       //    5972
                                                    AND postmeta_type.meta_key = '_mc_xii_variation_type'                            //    5973
                                                    AND postmeta_type.meta_value = '{$type_base}' )" );                              //    5974
    }   # public function sv_get_starting_sales() {                                                                                  //    5975
                                                                                                                                     //    5976
    # sv_get_ending_sales() gets base variations where sales are ending                                                              //    5977
                                                                                                                                     //    5978
    public function sv_get_ending_sales() {                                                                                          //    5979
        global $wpdb;                                                                                                                //    5980
        $type_base = MC_Simple_Variation_Functions::TYPE_BASE;                                                                       //    5981
        return $this->_get_ending_sales( "AND EXISTS ( SELECT * FROM {$wpdb->postmeta}  as postmeta_type                             //    5982
                                                WHERE postmeta_type.post_id = postmeta.post_id                                       //    5983
                                                    AND postmeta_type.meta_key = '_mc_xii_variation_type'                            //    5984
                                                    AND postmeta_type.meta_value = '{$type_base}' )" );                              //    5985
    }   # public function sv_get_ending_sales() {                                                                                    //    5986
                                                                                                                                     //    5987
    private function _get_starting_sales( $clause ) {                                                                                //    5988
        global $wpdb;                                                                                                                //    5989
        return $wpdb->get_col(                                                                                                       //    5990
            $wpdb->prepare(                                                                                                          //    5991
                "SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta                                                          //    5992
                LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id                                   //    5993
                LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id                                   //    5994
                WHERE postmeta.meta_key = '_sale_price_dates_from'                                                                   //    5995
                    AND postmeta_2.meta_key = '_price'                                                                               //    5996
                    AND postmeta_3.meta_key = '_sale_price'                                                                          //    5997
                    AND postmeta.meta_value > 0                                                                                      //    5998
                    AND postmeta.meta_value < %s                                                                                     //    5999
                    AND postmeta_2.meta_value != postmeta_3.meta_value                                                               //    6000
                    {$clause}",                                                                                                      //    6001
                current_time( 'timestamp', true )                                                                                    //    6002
            )                                                                                                                        //    6003
        );                                                                                                                           //    6004
    }   # private function _get_starting_sales( $clause ) {                                                                          //    6005
                                                                                                                                     //    6006
    private function _get_ending_sales( $clause ) {                                                                                  //    6007
        global $wpdb;                                                                                                                //    6008
        return $wpdb->get_col(                                                                                                       //    6009
            $wpdb->prepare(                                                                                                          //    6010
                "SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta                                                          //    6011
                LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id                                   //    6012
                LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id                                   //    6013
                WHERE postmeta.meta_key = '_sale_price_dates_to'                                                                     //    6014
                    AND postmeta_2.meta_key = '_price'                                                                               //    6015
                    AND postmeta_3.meta_key = '_regular_price'                                                                       //    6016
                    AND postmeta.meta_value > 0                                                                                      //    6017
                    AND postmeta.meta_value < %s                                                                                     //    6018
                    AND postmeta_2.meta_value != postmeta_3.meta_value                                                               //    6019
                    {$clause}",                                                                                                      //    6020
                current_time( 'timestamp', true )                                                                                    //    6021
            )                                                                                                                        //    6022
        );                                                                                                                           //    6023
    }   #  private function _get_ending_sales( $clause ) {                                                                           //    6024
                                                                                                                                     //    6025
}   # class MC_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT {                                                            //    6026
                                                                                                                                     //    6027
MC_Product_Data_Store_CPT::init();                                                                                                   //    6028
                                                                                                                                     //    6029
class MC_Product_Variable_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT {                                                //    6030
                                                                                                                                     //    6031
    public static function init() {                                                                                                  //    6032
        MC_Utility::add_filter( 'woocommerce_product-variable_data_store', 'mc_xii_woocommerce_product-variable_data_store',         //    6033
                function( $store ) {                                                                                                 //    6034
            return new MC_Product_Variable_Data_Store_CPT();                                                                         //    6035
        } );                                                                                                                         //    6036
    }                                                                                                                                //    6037
                                                                                                                                     //    6038
    public function read_children( &$product, $force_read = false ) {                                                                //    6040
        $children = parent::read_children( $product, $force_read );                                                                  //    6044
        # Remove base variations if $product is a Simple Variable product.                                                           //    6045
        if ( $this->is_simple_variable( $product ) ) {                                                                               //    6046
            # Remove the base variations from $children[ 'all' ] and $children[ 'visible' ] as these variations are not really       //    6047
            # variations.                                                                                                            //    6048
            $base_variation_ids  = $this->get_base_variation_ids( $product );                                                        //    6049
            $children[ 'all' ]     = array_diff( $children[ 'all' ],     $base_variation_ids );                                      //    6050
            $children[ 'visible' ] = array_diff( $children[ 'visible' ], $base_variation_ids );                                      //    6051
            $attributes_min = [];                                                                                                    //    6052
            $attributes_max = [];                                                                                                    //    6053
            $prices = $this->get_min_max_prices( $product );                                                                         //    6054
            foreach ( $prices as $attribute => &$price ) {                                                                           //    6055
                if ( MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {            //    6056
                    $price[ 'min'     ] = 0.0;                                                                                       //    6057
                    $price[ 'min_val' ] = MC_Simple_Variation_Functions::UNSELECTED;                                                 //    6058
                }                                                                                                                    //    6059
                $attributes_min[ 'attribute_' . $attribute ] = $price[ 'min_val' ];                                                  //    6060
                $attributes_max[ 'attribute_' . $attribute ] = $price[ 'max_val' ];                                                  //    6061
            }                                                                                                                        //    6062
            if ( get_post_meta( $product->get_id(), '_mc_xii_product_attributes_version_count', TRUE ) ) {                           //    6066
                # If the variations are virtual then they do not exists in the database and $children[ 'all' ] and                   //    6067
                # $children[ 'visible' ] will be empty. Since, there may be a humongous number of virtual variations and             //    6068
                # WC_Product_Variable_Data_Store_CPT::sync_price() will create a database row in table $wpdb->postmeta for each      //    6069
                # variation in $children[ 'visible' ] we do not want to $children[ 'visible' ] to contain all of these. Instead just //    6070
                # add entries for least expensive and most expensive variations so database queries on price will at least get the   //    6071
                # right range.                                                                                                       //    6072
                $product_data_store    = new MC_Product_Data_Store_CPT();                                                            //    6073
                $variation_id_min      = $product_data_store->find_matching_product_variation( $product, $attributes_min );          //    6074
                $variation_id_max      = $product_data_store->find_matching_product_variation( $product, $attributes_max );          //    6075
                $children[ 'all' ]     = [ $variation_id_min, $variation_id_max ];                                                   //    6076
                $children[ 'visible' ] = [ $variation_id_min, $variation_id_max ];                                                   //    6077
            }   # if ( get_post_meta( $product_id, '_mc_xii_product_attributes_version_count', TRUE ) ) {                            //    6079
        }                                                                                                                            //    6080
        return $children;                                                                                                            //    6081
    }   # public function read_children( &$product, $force_read = false ) {                                                          //    6082
                                                                                                                                     //    6083
    public function read_all_children( &$product, $force_read = FALSE ) {                                                            //    6084
        return parent::read_children( $product, $force_read );                                                                       //    6085
    }   # public function read_all_children( &$product, $force_read = FALSE ) {                                                      //    6086
                                                                                                                                     //    6087
    public function is_simple_variable( $product ) {                                                                                 //    6088
        return ! ! get_post_meta( $product->get_id(), '_mc_xii_is_simple_variable', TRUE );                                          //    6089
    }   # public function is_simple_variable( $product ) {                                                                           //    6090
                                                                                                                                     //    6091
    public function get_base_variation_ids( $product ) {                                                                             //    6092
        global $wpdb;                                                                                                                //    6093
        $base_variation_ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                 //    6094
SELECT p.ID FROM $wpdb->posts p, $wpdb->postmeta m                                                                                   //    6095
    WHERE p.ID = m.post_id AND p.post_parent = %d AND p.post_type = 'product_variation' AND m.meta_key = '_mc_xii_variation_type'    //    6096
        AND m.meta_value = 'base'                                                                                                    //    6097
EOD                                                                                                                                  //    6098
                                                            , $product->get_id() ) );                                                //    6099
        return $base_variation_ids;                                                                                                  //    6100
    }   # public function get_base_variations_ids( $product ) {                                                                      //    6101
                                                                                                                                     //    6102
    protected function read_attributes( &$product ) {                                                                                //    6104
        parent::read_attributes( $product );                                                                                         //    6106
        if ( ! $product instanceof MC_Product_Simple_Variable ) {                                                                    //    6107
            return;                                                                                                                  //    6108
        }                                                                                                                            //    6109
        $attributes = $product->get_attributes();                                                                                    //    6110
        $meta_attributes = get_post_meta( $product->get_id(), '_product_attributes', TRUE );                                         //    6111
        $new_attributes = [];                                                                                                        //    6112
        foreach ( $attributes as $name => $attribute ) {                                                                             //    6113
            $new_attribute = new MC_Product_Attribute( $attribute );                                                                 //    6114
            if ( ! empty( $meta_attributes[ sanitize_title( $name ) ][ 'is_optional' ] ) ) {                                         //    6115
                $new_attribute->set_optional( 1 );                                                                                   //    6116
            }                                                                                                                        //    6117
            // TODO: Below is a hack for backward compatibility                                                                      //    6118
            if ( strlen( $name ) > MC_Simple_Variation_Functions::$optional_suffix_length && ! substr_compare( $name,                //    6119
                    MC_Simple_Variation_Functions::OPTIONAL, - MC_Simple_Variation_Functions::$optional_suffix_length ) ) {          //    6120
                $new_attribute->set_optional( 1 );                                                                                   //    6121
            }                                                                                                                        //    6122
            $new_attributes[] = $new_attribute;                                                                                      //    6123
        }                                                                                                                            //    6124
        $product->set_attributes( $new_attributes );                                                                                 //    6125
        $attributes = $product->get_attributes();                                                                                    //    6126
    }   # protected function read_attributes( &$product ) {                                                                          //    6127
                                                                                                                                     //    6128
    protected function update_attributes( &$product, $force = false ) {                                                              //    6130
        parent::update_attributes( $product, $force );                                                                               //    6132
        if ( ! $product instanceof MC_Product_Simple_Variable ) {                                                                    //    6133
            return;                                                                                                                  //    6134
        }                                                                                                                            //    6135
        $attributes  = $product->get_attributes();                                                                                   //    6136
        $meta_values = get_post_meta( $product->get_id(), '_product_attributes', TRUE );                                             //    6137
        if ( ! $meta_values ) {                                                                                                      //    6138
            return;                                                                                                                  //    6139
        }                                                                                                                            //    6140
        foreach ( $meta_values as $key => &$meta_value ) {                                                                           //    6141
            $attribute = $attributes[ sanitize_title( $key ) ];                                                                      //    6142
            $meta_value[ 'is_optional' ] = $attribute->get_optional() ? 1 : 0;                                                       //    6143
        }                                                                                                                            //    6144
        update_post_meta( $product->get_id(), '_product_attributes', $meta_values );                                                 //    6145
    }   # protected function update_attributes( &$product, $force = false ) {                                                        //    6146
                                                                                                                                     //    6147
    # WC_Product_Variable_Data_Store_CPT::read_price_data() uses the variations to calculate the variation prices for a variable     //    6148
    # product. If the variable product uses virtual variation these variations do not exists so the variation prices must be         //    6149
    # calculated dynamically from the base variation prices.                                                                         //    6150
                                                                                                                                     //    6151
    public function read_price_data( &$product, $for_display = FALSE ) {                                                             //    6153
        global $wpdb;                                                                                                                //    6154
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $product->get_id() ) ) {                                              //    6155
            return parent::read_price_data( $product, $for_display );                                                                //    6156
        }                                                                                                                            //    6157
        $hide_out_of_stock_sql_0 = $hide_out_of_stock_sql_1 = $hide_out_of_stock_sql_2 = '';                                         //    6158
        if ( wc_string_to_bool( get_option( 'woocommerce_hide_out_of_stock_items' ) ) ) {                                            //    6159
            $hide_out_of_stock_sql_0 = "$wpdb->postmeta m2,";                                                                        //    6160
            $hide_out_of_stock_sql_1 = 'AND m0.post_id = m2.post_id';                                                                //    6161
            $hide_out_of_stock_sql_2 = 'AND m2.meta_key = "_stock_status" AND m2.meta_value != "outofstock"';                        //    6162
        }                                                                                                                            //    6163
        $prices              = [];                                                                                                   //    6164
        $price_for_attribute = [];                                                                                                   //    6165
        $on_sale             = TRUE;                                                                                                 //    6166
        foreach ( [ '_price', '_regular_price' ] as $price_type ) {                                                                  //    6167
            $results = $wpdb->get_results(  $wpdb->prepare( <<<EOD                                                                   //    6168
SELECT m0.meta_key attribute, m0.meta_value value, m1.meta_value price FROM $wpdb->postmeta m0, $wpdb->postmeta m1,                  //    6169
    $hide_out_of_stock_sql_0 $wpdb->posts p                                                                                          //    6170
WHERE m0.post_id = m1.post_id AND m0.post_id = p.id $hide_out_of_stock_sql_1                                                         //    6171
    AND p.post_parent = %d AND p.post_type = 'product_variation' AND p.post_status = 'publish'                                       //    6172
    AND m0.meta_key LIKE 'attribute_%' AND m0.meta_value != 'mc_xii_not_selected' $hide_out_of_stock_sql_2                           //    6173
    AND m1.meta_key = '$price_type'                                                                                                  //    6174
EOD                                                                                                                                  //    6175
                , $product->get_id() ) );                                                                                            //    6176
            $min     = [];                                                                                                           //    6177
            $max     = [];                                                                                                           //    6178
            $min_key = [];                                                                                                           //    6179
            $max_key = [];                                                                                                           //    6180
            foreach ( $results as $result ) {                                                                                        //    6181
                $attribute = $result->attribute;                                                                                     //    6182
                $price     = $result->price;                                                                                         //    6183
                $key       = $result->value;                                                                                         //    6184
                if ( ! is_numeric( $price ) ) {                                                                                      //    6185
                    continue;                                                                                                        //    6186
                }                                                                                                                    //    6187
                if ( $price_type === '_price' ) {                                                                                    //    6188
                    $price_for_attribute[ $attribute ] = $price;                                                                     //    6189
                } else {                                                                                                             //    6190
                    $on_sale &= $price_for_attribute[ $attribute ] < $price;                                                         //    6191
                }                                                                                                                    //    6192
                if ( ! array_key_exists( $attribute, $max ) ) {                                                                      //    6193
                    if ( ! MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {      //    6194
                        $min[ $attribute ]     = $price;                                                                             //    6195
                        $min_key[ $attribute ] = $key;                                                                               //    6196
                    } else {                                                                                                         //    6197
                        $min[ $attribute ]     = 0;                                                                                  //    6198
                        $min_key[ $attribute ] = MC_Simple_Variation_Functions::UNSELECTED;                                          //    6199
                    }                                                                                                                //    6200
                    $max[ $attribute ]     = $price;                                                                                 //    6201
                    $max_key[ $attribute ] = $key;                                                                                   //    6202
                } else {                                                                                                             //    6203
                    if ( ! MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {      //    6204
                        if ( $price < $min[ $attribute ] ) {                                                                         //    6205
                            $min[ $attribute ]     = $price;                                                                         //    6206
                            $min_key[ $attribute ] = $key;                                                                           //    6207
                        }                                                                                                            //    6208
                    }                                                                                                                //    6209
                    if ( $price > $max[ $attribute ] ) {                                                                             //    6210
                        $max[ $attribute ]     = $price;                                                                             //    6211
                        $max_key[ $attribute ] = $key;                                                                               //    6212
                    }                                                                                                                //    6213
                }                                                                                                                    //    6214
            }                                                                                                                        //    6215
            # Verify that all component types have at least one component in stock.                                                  //    6216
            if ( count( $min_key ) < count( $product->get_variation_attributes() ) ) {                                               //    6217
                return [ 'price' => [], 'regular_price' => [], 'sale_price' => [] ];                                                 //    6218
            }                                                                                                                        //    6219
            $min_price = 0;                                                                                                          //    6220
            foreach ( $min as $value ) {                                                                                             //    6221
                $min_price += $value;                                                                                                //    6222
            }                                                                                                                        //    6223
            $max_price = 0;                                                                                                          //    6224
            foreach ( $max as $value ) {                                                                                             //    6225
                $max_price += $value;                                                                                                //    6226
            }                                                                                                                        //    6227
            $data_store       = WC_Data_Store::load( 'product' );                                                                    //    6228
            $min_variation_id = $data_store->find_matching_product_variation( $product, $min_key );                                  //    6229
            $max_variation_id = $data_store->find_matching_product_variation( $product, $max_key );                                  //    6230
            $prices[ trim( $price_type, '_' ) ] = [ $min_variation_id => $min_price, $max_variation_id => $max_price ];              //    6231
            # TODO: _sale_price                                                                                                      //    6232
        }   # foreach ( [ '_price', '_regular_price' ] as $price_type ) {                                                            //    6233
        # refer to is_on_WC_Product_Variable::sale() for why $prices['sale_price'] is set as follows                                 //    6234
        $prices['sale_price'] = $on_sale ? $prices['price'] : $prices['regular_price'];                                              //    6235
        # following derived from WC_Product_Variable_Data_Store_CPT::read_price_data()                                               //    6236
        if ( $for_display ) {                                                                                                        //    6237
            $incl = get_option( 'woocommerce_tax_display_shop' ) === 'incl';                                                         //    6238
            $args = [ 'qty' => 1 ];                                                                                                  //    6239
            foreach ( [ 'price', 'regular_price', 'sale_price' ] as $price_type ) {                                                  //    6240
                foreach ( $prices[ $price_type ] as $variation_id => &$price ) {                                                     //    6241
                    if ( $price !== '' ) {                                                                                           //    6242
                        $variation     = wc_get_product( $variation_id );                                                            //    6243
                        $args['price'] = $price;                                                                                     //    6244
                        $price         = $incl ? wc_get_price_including_tax( $variation, $args )                                     //    6245
                                               : wc_get_price_excluding_tax( $variation, $args );                                    //    6246
                    }                                                                                                                //    6247
                    $price = wc_format_decimal( $price, wc_get_price_decimals() );                                                   //    6248
                }   # foreach ( $prices[ $price_type ] as &$price ) {                                                                //    6249
            }   # foreach ( [ 'price', 'regular_price', 'sale_price' ] as $price_type ) {                                            //    6250
        }   # if ( $for_display ) {                                                                                                  //    6251
        return $prices;                                                                                                              //    6252
    }   # public function read_price_data( &$product, $for_display = FALSE ) {                                                       //    6253
                                                                                                                                     //    6254
    public function sync_price( &$product ) {                                                                                        //    6256
        if ( MC_Product_Data_Store_CPT::is_virtual_variable( $product->get_id() ) ) {                                                //    6257
            $this->sv_sync_price( $product );                                                                                        //    6258
            return;                                                                                                                  //    6259
        }                                                                                                                            //    6260
        parent::sync_price( $product );                                                                                              //    6261
    }   # public function sync_price( &$product ) {                                                                                  //    6262
                                                                                                                                     //    6263
    # WC_Product_Variable_Data_Store_CPT::sync_price() creates a row with meta_key == '_price' in $wpdb->postmeta for every          //    6264
    # variation. Since, virtual variable products may have a humongous number of variations this is not practical. Rather,           //    6265
    # with respect to how Classic Commerce currently uses these rows it is sufficient just to have rows for the mininum and          //    6266
    # maximum price. See WC_Product_Variable::get_variation_prices().                                                                //    6267
                                                                                                                                     //    6268
    private function sv_sync_price( &$product ) {                                                                                    //    6269
        $prices = $product->get_min_max_price();                                                                                     //    6271
        $min    = $prices[ 'min' ];                                                                                                  //    6272
        $max    = $prices[ 'max' ];                                                                                                  //    6273
        delete_post_meta( $product->get_id(), '_price' );                                                                            //    6275
        delete_post_meta( $product->get_id(), '_sale_price' );                                                                       //    6276
        delete_post_meta( $product->get_id(), '_regular_price' );                                                                    //    6277
        if ( $min !== '' ) {                                                                                                         //    6278
            add_post_meta( $product->get_id(), '_price', $min, false );                                                              //    6279
        }                                                                                                                            //    6280
        if ( $max !== '' ) {                                                                                                         //    6281
            add_post_meta( $product->get_id(), '_price', $max, false );                                                              //    6282
        }                                                                                                                            //    6283
    }   # private function sv_sync_price( &$product ) {                                                                              //    6284
                                                                                                                                     //    6285
    public function get_min_max_prices( &$product, $flags = [] ) {                                                                   //    6286
        global $wpdb;                                                                                                                //    6287
        $in_stock_only = in_array( 'in_stock', $flags );                                                                             //    6289
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                        //    6290
SELECT m.post_id, m.meta_key, m.meta_value                                                                                           //    6291
    FROM $wpdb->postmeta m, $wpdb->posts p, $wpdb->postmeta n                                                                        //    6292
    WHERE m.post_id = p.ID AND n.post_id = p.ID                                                                                      //    6293
        AND p.post_parent = %d AND p.post_status = 'publish'                                                                         //    6294
        AND n.meta_key = '_mc_xii_variation_type' AND n.meta_value = 'base'                                                          //    6295
        AND (m.meta_key LIKE 'attribute_%' OR m.meta_key LIKE '_%price')                                                             //    6296
EOD                                                                                                                                  //    6297
                                                     , $product->get_id() ) );                                                       //    6298
        $in_stock_ids = [];                                                                                                          //    6300
        if ( $in_stock_only ) {                                                                                                      //    6301
        $in_stock_ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                       //    6302
SELECT m.post_id                                                                                                                     //    6303
    FROM $wpdb->postmeta m, $wpdb->posts p, $wpdb->postmeta n                                                                        //    6304
    WHERE m.post_id = p.ID AND n.post_id = p.ID                                                                                      //    6305
        AND p.post_parent = %d AND p.post_status = 'publish'                                                                         //    6306
        AND n.meta_key = '_mc_xii_variation_type' AND n.meta_value = 'base'                                                          //    6307
        AND m.meta_key = '_stock_status' AND m.meta_value = 'instock'                                                                //    6308
EOD                                                                                                                                  //    6309
                                                      , $product->get_id() ) );                                                      //    6310
        }                                                                                                                            //    6311
        $data = [];                                                                                                                  //    6313
        foreach ( $results as $result ) {                                                                                            //    6314
            $post_id      = $result->post_id;                                                                                        //    6315
            $key          = $result->meta_key;                                                                                       //    6316
            $value        = $result->meta_value;                                                                                     //    6317
            $not_in_stock = $in_stock_only && ! in_array( $post_id, $in_stock_ids );                                                 //    6318
            if ( ! array_key_exists( $post_id, $data ) ) {                                                                           //    6319
                $data[ $post_id ] = [ 'attribute' => null, 'value' => null, 'price' => null ];                                       //    6320
            }                                                                                                                        //    6321
            $datum =& $data[ $post_id ];                                                                                             //    6322
            if ( substr_compare( $key, 'attribute_', 0, 10 ) === 0 && $value !== 'mc_xii_not_selected' ) {                           //    6323
                $datum[ 'attribute' ] = substr( $key, 10 );                                                                          //    6324
                $datum[ 'value'     ] = $value;                                                                                      //    6325
            }                                                                                                                        //    6326
            if ( $key === '_price' ) {                                                                                               //    6327
                $datum[ 'price' ] = $not_in_stock ? '' : $value;                                                                     //    6328
            }                                                                                                                        //    6329
            unset( $datum );                                                                                                         //    6330
        }                                                                                                                            //    6331
        $prices = [];                                                                                                                //    6333
        foreach ( $data as $post_id => $datum ) {                                                                                    //    6334
            $attribute = $datum[ 'attribute' ];                                                                                      //    6335
            $value     = $datum[ 'value' ];                                                                                          //    6336
            $price     = $datum[ 'price' ];                                                                                          //    6337
            $price     = is_null( $price ) || '' === $price ? '' : (float) $price;                                                   //    6338
            if ( ! array_key_exists( $attribute, $prices ) ) {                                                                       //    6339
                $prices[ $attribute ] = [ 'min' => PHP_INT_MAX, 'max' => PHP_INT_MIN, 'min_val' => '', 'max_val' => '' ];            //    6340
            }                                                                                                                        //    6341
            $prices_for_attribute =& $prices[ $attribute ];                                                                          //    6342
            if ( $price !== '' && $price < $prices_for_attribute[ 'min' ] ) {                                                        //    6343
                $prices_for_attribute[ 'min' ]     = $price;                                                                         //    6344
                $prices_for_attribute[ 'min_val' ] = $value;                                                                         //    6345
            }                                                                                                                        //    6346
            if ( $price !== '' && $price > $prices_for_attribute[ 'max' ] ) {                                                        //    6347
                $prices_for_attribute[ 'max' ]     = $price;                                                                         //    6348
                $prices_for_attribute[ 'max_val' ] = $value;                                                                         //    6349
            }                                                                                                                        //    6350
            unset( $prices_for_attribute );                                                                                          //    6351
        }                                                                                                                            //    6352
        foreach ( $prices as &$prices_for_attribute ) {                                                                              //    6353
            foreach ( $prices_for_attribute as &$price ) {                                                                           //    6354
                if ( $price === PHP_INT_MAX || $price === PHP_INT_MIN ) {                                                            //    6355
                    $price = '';                                                                                                     //    6356
                }                                                                                                                    //    6357
            }                                                                                                                        //    6358
            unset( $price );                                                                                                         //    6359
        }                                                                                                                            //    6360
        unset( $prices_for_attribute );                                                                                              //    6361
        return $prices;                                                                                                              //    6363
    }   # public function get_min_max_prices( &$product ) {                                                                          //    6364
                                                                                                                                     //    6365
    public function child_is_in_stock( $product ) {                                                                                  //    6367
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $product->get_id() ) ) {                                              //    6369
            return parent::child_is_in_stock( $product );                                                                            //    6370
        }                                                                                                                            //    6371
        # Since virtual variations do not exists in the database parent::child_is_in_stock( $product ) will not work;                //    6373
        return is_numeric( $product->get_min_max_price()[ 'min' ] );                                                                 //    6374
    }   # public function child_is_in_stock( $product ) {                                                                            //    6375
                                                                                                                                     //    6376
    public function sync_managed_variation_stock_status( &$product ) {                                                               //    6378
        global $wpdb;                                                                                                                //    6379
        if ( $product->get_manage_stock() ) {                                                                                        //    6382
            $wpdb->query( $wpdb->prepare( <<<EOD                                                                                     //    6383
UPDATE $wpdb->postmeta m, $wpdb->posts p SET m.meta_value = 'no'                                                                     //    6384
    WHERE m.post_id = p.ID AND p.post_parent = %d AND (m.meta_key = '_manage_stock' OR m.meta_key = '_backorders')                   //    6385
EOD                                                                                                                                  //    6386
                                        , $product->get_id() ) );                                                                    //    6387
            $wpdb->query( $wpdb->prepare( <<<EOD                                                                                     //    6388
UPDATE $wpdb->postmeta m, $wpdb->posts p SET m.meta_value = ''                                                                       //    6389
    WHERE m.post_id = p.ID AND p.post_parent = %d AND (m.meta_key = '_stock' OR m.meta_key = '_low_stock_amount')                    //    6390
EOD                                                                                                                                  //    6391
                                        , $product->get_id() ) );                                                                    //    6392
        }                                                                                                                            //    6393
        parent::sync_managed_variation_stock_status( $product );                                                                     //    6394
    }   # public function sync_managed_variation_stock_status( &$product ) {                                                         //    6395
                                                                                                                                     //    6396
    public function get_number_of_variations( &$product ) {                                                                          //    6397
        $attributes = get_post_meta( $product->get_id(), '_product_attributes', TRUE );                                              //    6398
        $number     = 1;                                                                                                             //    6399
        foreach ( $attributes as $slug => $attribute ) {                                                                             //    6400
            $count = count( explode( '|', $attribute[ 'value' ] ) ) - 1;                                                             //    6401
            if ( $attribute[ 'is_optional' ] ) {                                                                                     //    6402
                ++$count;                                                                                                            //    6403
            }                                                                                                                        //    6404
            $number *= $count;                                                                                                       //    6405
        }                                                                                                                            //    6406
        return $number;                                                                                                              //    6407
    }   # public function get_number_of_variations( &$product ) {                                                                    //    6408
                                                                                                                                     //    6409
    # get_variation_counts() requires that all base variations exists in the database, i.e., it will not work immediately after a    //    6410
    # call to canonicalize_variations() as missing base variations are generated later in the first phase of the call to             //    6411
    # sync_compound_variations_with_base_variations().                                                                               //    6412
                                                                                                                                     //    6413
    public function get_variation_counts( $product_id ) {                                                                            //    6414
        global $wpdb;                                                                                                                //    6415
        $type_base = MC_Simple_Variation_Functions::TYPE_BASE;                                                                       //    6416
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                        //    6417
SELECT n.meta_key attribute, n.meta_value value FROM $wpdb->posts p, $wpdb->postmeta m, $wpdb->postmeta n                            //    6418
    WHERE p.post_parent = %d AND m.post_id = p.id AND n.post_id = p.id                                                               //    6419
        AND m.meta_key = '_mc_xii_variation_type' AND m.meta_value = '$type_base'                                                    //    6420
        AND n.meta_key LIKE 'attribute_%' AND n.meta_value !='mc_xii_not_selected'                                                   //    6421
    ORDER BY attribute                                                                                                               //    6422
EOD                                                                                                                                  //    6423
            , $product_id ) );                                                                                                       //    6424
        if ( ! $results ) {                                                                                                          //    6425
            return NULL;                                                                                                             //    6426
        }                                                                                                                            //    6427
        $counts                 = [];                                                                                                //    6428
        $optional_count         = 0;                                                                                                 //    6429
        foreach ( $results as $result ) {                                                                                            //    6430
            if ( array_key_exists( $result->attribute, $counts ) ) {                                                                 //    6431
                ++$counts[ $result->attribute ];                                                                                     //    6432
            } else {                                                                                                                 //    6433
                $counts[ $result->attribute ] = 1;                                                                                   //    6434
                if ( MC_Product_Attribute::is_optional_attribute_from_database( $result->attribute, $product_id ) ) {                //    6435
                    ++$counts[ $result->attribute ];                                                                                 //    6436
                    ++$optional_count;                                                                                               //    6437
                }                                                                                                                    //    6438
            }                                                                                                                        //    6439
        }                                                                                                                            //    6440
        $variation_counts      = 1;                                                                                                  //    6441
        $base_variation_counts = 0;                                                                                                  //    6442
        $pretty_counts         = [];                                                                                                 //    6443
        $optional              = [];                                                                                                 //    6444
        foreach ( $counts as $attribute => $count ) {                                                                                //    6445
            $variation_counts      *= $count;                                                                                        //    6446
            $base_variation_counts += $count;                                                                                        //    6447
            if ( MC_Product_Attribute::is_optional_attribute_from_database( $attribute, $product_id ) ) {                            //    6448
                $pretty_attribute                   = substr( $attribute, 10,                                                        //    6449
                                                              - MC_Simple_Variation_Functions::$optional_suffix_length );            //    6450
                $pretty_counts[ $pretty_attribute ] = $count - 1;                                                                    //    6451
                $optional[]                         = $pretty_attribute;                                                             //    6452
            } else {                                                                                                                 //    6453
                $pretty_counts[ substr( $attribute, 10 ) ] = $count;                                                                 //    6454
            }                                                                                                                        //    6455
        }                                                                                                                            //    6456
        $base_variation_counts -= $optional_count;                                                                                   //    6457
        return [ MC_Simple_Variation_Functions::TYPE_BASE => $base_variation_counts,                                                 //    6458
                 MC_Simple_Variation_Functions::TYPE_COMPOUND => $variation_counts,                                                  //    6459
                 'counts_by_attributes' => $pretty_counts,                                                                           //    6460
                 'optional' => $optional ];                                                                                          //    6461
    }   # public function get_variation_counts( $product_id ) {                                                                      //    6462
                                                                                                                                     //    6463
    public function get_base_variations_for_attributes( $product, &$attributes, &$attribute_values,                                  //    6464
                                                        &$map_attributes_to_variation ) {                                            //    6465
        $product = MC_Simple_Variation_Functions::get_product( $product, $post_id );                                                 //    6467
        MC_Simple_Variation_Functions::load_variations_from_database( $product, $attributes, $attribute_values,                      //    6468
                                                                    $map_attributes_to_variation );                                  //    6469
        $attribute_values_keys = array_keys( $attribute_values );                                                                    //    6470
        $base_variation_for_attribute = [];                                                                                          //    6471
        foreach ( $attribute_values_keys as $attribute ) {                                                                           //    6472
            foreach ( $attribute_values[ $attribute ] as $value ) {                                                                  //    6473
                if ( $value === MC_Simple_Variation_Functions::UNSELECTED ) {                                                        //    6474
                    continue;                                                                                                        //    6475
                }                                                                                                                    //    6476
                $variation = MC_Simple_Variation_Functions::get_base_variation_for_attribute( $post_id, $attribute, $value,          //    6477
                                 $map_attributes_to_variation, $attribute_values_keys, $the_attributes,                              //    6478
                                 $product->get_meta( '_mc_xii_version' ) );                                                          //    6479
                if ( empty( $base_variation_for_attribute[ $attribute ] ) ) {                                                        //    6480
                    $base_variation_for_attribute[ $attribute ] = [];                                                                //    6481
                }                                                                                                                    //    6482
                $base_variation_for_attribute[ $attribute ][ $value ] = $variation;                                                  //    6483
            }                                                                                                                        //    6484
        }                                                                                                                            //    6485
        return $base_variation_for_attribute;                                                                                        //    6486
    }   # public function get_base_variations_for_attributes( $product, &$attributes, &$attribute_values,                            //    6487
                                                                                                                                     //    6488
}   # class MC_Product_Variable_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT {                                          //    6489
                                                                                                                                     //    6490
MC_Product_Variable_Data_Store_CPT::init();                                                                                          //    6491
                                                                                                                                     //    6492
class MC_Product_Variation_Data_Store_CPT extends WC_Product_Variation_Data_Store_CPT {                                              //    6493
                                                                                                                                     //    6494

### REDACTED lines 6495 -> 6499 redacted,      5 lines redacted. ###

                                                                                                                                     //    6500
    public static function init() {                                                                                                  //    6501
        # filter 'woocommerce_product-variation_data_store' is applied in WC_Data_Store::__construct()                               //    6502
        MC_Utility::add_filter( 'woocommerce_product-variation_data_store', 'mc_xii_woocommerce_product-variation_data_store',       //    6503
                function( $store ) {                                                                                                 //    6504
            return new MC_Product_Variation_Data_Store_CPT();                                                                        //    6505
        } );                                                                                                                         //    6506
                                                                                                                                     //    6507

### REDACTED lines 6508 -> 6519 redacted,     12 lines redacted. ###

                                                                                                                                     //    6520
        MC_Hook_Wrapper::wrap_hook(                                                                                                  //    6521
                'wc_maybe_reduce_stock_levels',                                                                                      //    6522
                function( $callback, $order_id ) {                                                                                   //    6523
                    # provide order context for code lower down                                                                      //    6524
                    $context = new MC_Context( 'order_id', $order_id );                                                              //    6525
                    call_user_func( $callback, $order_id );                                                                          //    6526
                },                                                                                                                   //    6527
                [ 'woocommerce_payment_complete', 'woocommerce_order_status_completed', 'woocommerce_order_status_processing',       //    6528
                        'woocommerce_order_status_on-hold' ],                                                                        //    6529
                'wc_maybe_reduce_stock_levels', TRUE );                                                                              //    6530
        MC_Hook_Wrapper::wrap_hook(                                                                                                  //    6531
                'wc_maybe_increase_stock_levels',                                                                                    //    6532
                function( $callback, ...$args ) {                                                                                    //    6533
                    try {                                                                                                            //    6534
                        # provide order context for code lower down                                                                  //    6535
                        $i = MC_Context::push( 'order_id', $args[0] );                                                               //    6536
                        MC_Context::dump( 'wc_maybe_increase_stock_levels' );                                                        //    6537
                        call_user_func_array( $callback, $args );                                                                    //    6538
                    } finally {                                                                                                      //    6539
                        MC_Context::pop_to( $i );                                                                                    //    6540
                        MC_Context::dump( 'wc_maybe_increase_stock_levels' );                                                        //    6541
                    }                                                                                                                //    6542
                },                                                                                                                   //    6543
                [ 'woocommerce_order_status_cancelled', 'woocommerce_order_status_pending' ],                                        //    6544
                'wc_maybe_increase_stock_levels', TRUE );                                                                            //    6545
    }   # public static function init() {                                                                                            //    6546
                                                                                                                                     //    6547

### REDACTED lines 6548 -> 6814 redacted,    267 lines redacted. ###

                                                                                                                                     //    6815
    # canonicalize_variations() adjust existing variations to be consistent with the product attributes of its parent variable       //    6816
    # product. Classic Commerce does this lazily only when the variation is updated using                                            //    6817
    # WC_Product_Variation_Data_Store_CPT::update_attributes() which is called from WC_Product_Variation_Data_Store_CPT::update().   //    6818
    # However, this can cause WC_Product_Data_Store_CPT::find_matching_product_variation to return incorrect results as it runs      //    6819
    # directly off the database. This of course is a transient problem as the user will probably manually update the corresponding   //    6820
    # variations soon after updating the attributes. However, canonicalize_variations() will automatically update the database       //    6821
    # immediately after the new attributes are saved. canonicalize_variations() only handles existing variations. If new variations  //    6822
    # are needed they will be generate later by MC_Simple_Variation_Functions::sync_compound_variations_with_base_variations().      //    6823
    # N.B. For simple variable products with a huge number of variations canonicalize_variations() may not be able to update all     //    6824
    # the variations in a single call as the PHP execution time limit may be exceeded. Multiple calls may be necessary and           //    6825
    # subsequent calls must set $continue to TRUE. canonicalize_variations() should return FALSE if it cannot process all the        //    6826
    # variations. The return value is only used to send messages to the frontend. The critical state data is always saved in the     //    6827
    # database so recovery can be done in the event the session is terminated because the PHP execution time limit is exceeded.      //    6828
                                                                                                                                     //    6829
    public function canonicalize_variations( $product, $continue = FALSE ) {                                                         //    6830
        global $wpdb;                                                                                                                //    6831
        $product = MC_Simple_Variation_Functions::get_product( $product, $post_id );                                                 //    6833
        if ( MC_Simple_Variation_Functions::is_malconfigured( $post_id ) ) {                                                         //    6834
            return TRUE;                                                                                                             //    6835
        }                                                                                                                            //    6836
        $version = $product->get_meta( '_mc_xii_version' );                                                                          //    6837
        if ( $continue ) {                                                                                                           //    6840
        }                                                                                                                            //    6842
        if ( ! $continue ) {                                                                                                         //    6843
            $version = $version ? $version + 1 : 1;                                                                                  //    6844
            update_post_meta( $post_id, '_mc_xii_version', $version );                                                               //    6845
            update_post_meta( $post_id, '_mc_xii_doing', 'canonicalize_variation_attributes' );                                      //    6846
        }                                                                                                                            //    6848
        $attribute_values = wc_list_pluck( array_filter( $product->get_attributes(), 'wc_attributes_array_filter_variation' ),       //    6849
                                           'get_slugs' );                                                                            //    6850
        $attribute_keys   = array_keys( $attribute_values );                                                                         //    6851
        # must directly access database data to handle variations with attributes that were changed from not optional to optional    //    6852
        # or vice versa since WC_Product_Variation objects will ignore invalid attributes on construction - see                      //    6853
        # wc_get_product_variation_attributes()                                                                                      //    6854
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                        //    6855
SELECT m.post_id, m.meta_key, m.meta_value FROM $wpdb->postmeta m, $wpdb->posts p                                                    //    6856
    WHERE m.post_id = p.ID AND p.post_parent = %d AND p.post_type = 'product_variation' AND m.meta_key LIKE 'attribute_%%'           //    6857
    ORDER BY m.post_id                                                                                                               //    6858
EOD                                                                                                                                  //    6859
            , $post_id ) );                                                                                                          //    6860
        $database_variation_attributes = [];                                                                                         //    6861
        foreach ( $results as $result ) {                                                                                            //    6862
            # check if attribute changed optional property                                                                           //    6863
            $attribute = substr( $result->meta_key, 10 );                                                                            //    6864
            if ( ! $continue ) {                                                                                                     //    6865
                if ( ! in_array( $attribute, $attribute_keys ) ) {                                                                   //    6866
                    if ( MC_Simple_Variation_Functions::is_optional_attribute_obsolete( $attribute ) ) {                             //    6867
                        $new_attribute = MC_Simple_Variation_Functions::remove_optional_suffix( $attribute );                        //    6868
                    } else {                                                                                                         //    6869
                        $new_attribute = $attribute . MC_Simple_Variation_Functions::OPTIONAL;                                       //    6870
                    }                                                                                                                //    6871
                    if ( in_array( $new_attribute, $attribute_keys ) ) {                                                             //    6872
                        delete_post_meta( $result->post_id, $result->meta_key );                                                     //    6873
                        # save attributes as they exists in the database since the API will sanitize the attributes                  //    6874
                        update_post_meta( $result->post_id, 'attribute_' . $new_attribute, $result->meta_value );                    //    6875
                        $attribute = $new_attribute;                                                                                 //    6876
                    }                                                                                                                //    6877
                }                                                                                                                    //    6878
            }                                                                                                                        //    6879
            if ( ! array_key_exists( $result->post_id, $database_variation_attributes ) ) {                                          //    6880
                $database_variation_attributes[ $result->post_id ] = [];                                                             //    6881
            }                                                                                                                        //    6882
            $database_variation_attributes[ $result->post_id ][ $attribute ] = $result->meta_value;                                  //    6883
        }                                                                                                                            //    6884
        $variation_ids = MC_Simple_Variation_Functions::get_children( $product, TRUE );                                              //    6885
        foreach ( $variation_ids as $variation_id ) {                                                                                //    6886
            if ( get_post_meta( $variation_id, '_mc_xii_version', TRUE ) == $version ) {                                             //    6887
                continue;                                                                                                            //    6888
            }                                                                                                                        //    6889
            if ( MC_Execution_Time::near_max_execution_limit() ) {                                                                   //    6890
                return FALSE;                                                                                                        //    6891
            }                                                                                                                        //    6892
            $variation            = new WC_Product_Variation( $variation_id );                                                       //    6893
            $variation_attributes = $variation->get_attributes();                                                                    //    6894
            $total_components     = 0;                                                                                               //    6895
            foreach ( $variation_attributes as $attribute => $value ) {                                                              //    6896
                if ( ! $value ) {                                                                                                    //    6897
                    $value = MC_Simple_Variation_Functions::UNSELECTED;                                                              //    6898
                }                                                                                                                    //    6899
                if ( ! in_array( $attribute, $attribute_keys ) ) {                                                                   //    6900
                    # This variation has an attribute that does not exists in its parent variable product.                           //    6901
                    # This should not be possible because when a variation is read from the database                                 //    6902
                    # wc_get_product_variation_attributes() is called to sanitize the attributes.                                    //    6903
                    error_log( 'ERROR: MC_Simple_Variation_Functions::canonicalize_variations(): Invalid attribute' );               //    6904
                    $variation->delete( TRUE );                                                                                      //    6905
                    continue 2;                                                                                                      //    6907
                }                                                                                                                    //    6908
                if ( ! in_array( $value, $attribute_values[ $attribute ] ) ) {                                                       //    6909
                    # This variation has a non existent attribute value.                                                             //    6910
                    $variation->delete( TRUE );                                                                                      //    6912
                    continue 2;                                                                                                      //    6913
                }                                                                                                                    //    6914
                if ( $value !== MC_Simple_Variation_Functions::UNSELECTED ) {                                                        //    6915
                    ++$total_components;                                                                                             //    6916
                }                                                                                                                    //    6917
            }   # foreach ( $variation_attributes as $attribute => $value ) {                                                        //    6918
            # handle variations that use a non existent component group in the database but the component is hidden by the API       //    6919
            foreach ( $database_variation_attributes[ $variation_id ] as $database_variation_attribute                               //    6920
                    => $database_variation_value ) {                                                                                 //    6921
                if ( ! in_array( $database_variation_attribute, $attribute_keys ) && $database_variation_value                       //    6922
                        !== MC_Simple_Variation_Functions::UNSELECTED ) {                                                            //    6923
                    $variation->delete( TRUE );                                                                                      //    6924
                    continue 2;                                                                                                      //    6926
               }                                                                                                                     //    6927
            }                                                                                                                        //    6928
            $variation_attribute_keys   = array_keys( $variation_attributes );                                                       //    6929
            $attribute_added            = FALSE;                                                                                     //    6930
            $missing_required_component = FALSE;                                                                                     //    6931
            foreach ( $attribute_keys as $attribute ) {                                                                              //    6932
                if ( empty( $variation_attributes[ $attribute ] ) ) {                                                                //    6933
                    $variation_attributes[ $attribute ] = MC_Simple_Variation_Functions::UNSELECTED;                                 //    6934
                    $attribute_added = TRUE;                                                                                         //    6935
                    if ( ! MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {      //    6937
                        $missing_required_component = TRUE;                                                                          //    6938
                    }                                                                                                                //    6939
                }                                                                                                                    //    6940
            }                                                                                                                        //    6941
            if ( $missing_required_component && $total_components > 1 ) {                                                            //    6942
                # compound variation with missing required component                                                                 //    6943
                $variation->delete( TRUE );                                                                                          //    6944
                continue;                                                                                                            //    6946
            }                                                                                                                        //    6947
            if ( $attribute_added ) {                                                                                                //    6948
                $variation->set_attributes( $variation_attributes );                                                                 //    6949
                $variation->save();                                                                                                  //    6950
            }                                                                                                                        //    6952
            $attribute_keys_list = '"' . implode( '", "', array_map( function( $k ) {                                                //    6953
                return esc_sql( 'attribute_' . $k );                                                                                 //    6954
            }, $attribute_keys ) ) . '"';                                                                                            //    6955
            if ( $wpdb->get_var( $wpdb->prepare( <<<EOD                                                                              //    6956
SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( $attribute_keys_list )                 //    6957
    AND post_id = %d AND meta_value != %s                                                                                            //    6958
EOD                                                                                                                                  //    6959
                , $variation_id, MC_Simple_Variation_Functions::UNSELECTED ) ) ) {                                                   //    6960
                # This variation is invalid since it has an attribute that does not exists in its parent variable product.           //    6961
                # Classic Commerce only deletes the attribute using WC_Product_Variation_Data_Store_CPT::update_attributes().        //    6962
                # I think this is wrong because this can result in multiple variations with exactly the same attributes.             //    6963
                # I think the variation itself must be deleted.                                                                      //    6964
                $variation->delete( TRUE );                                                                                          //    6965
                continue;                                                                                                            //    6967
            }                                                                                                                        //    6968
            # below extracted from WC_Product_Variation_Data_Store_CPT::update_attributes() to remove non existent attributes        //    6969
            $delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                          //    6970
SELECT meta_key FROM $wpdb->postmeta WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( $attribute_keys_list )                 //    6971
                                                                        AND post_id = %d                                             //    6972
EOD                                                                                                                                  //    6973
                                                                     , $variation_id ) );                                            //    6974
            foreach ( $delete_attribute_keys as $key ) {                                                                             //    6975
                delete_post_meta( $variation_id, $key );                                                                             //    6976
            }                                                                                                                        //    6977
            update_post_meta( $variation->get_id(), '_mc_xii_version', $version );                                                   //    6978
        }   # foreach ( $variation_ids as $variation_id ) {                                                                          //    6980
        update_post_meta( $post_id, '_mc_xii_doing', 'waiting_for_sync' );                                                           //    6981
        update_post_meta( $post_id, '_mc_xii_updated_version', $version );                                                           //    6982
        return TRUE;                                                                                                                 //    6984
    }   # private static function canonicalize_variations( $product ) {                                                              //    6985
                                                                                                                                     //    6986
}   # class MC_Product_Variation_Data_Store_CPT extends WC_Product_Variation_Data_Store_CPT {                                        //    6987
                                                                                                                                     //    6988
MC_Product_Variation_Data_Store_CPT::init();                                                                                         //    6989
                                                                                                                                     //    6990
# MC_AJAX adds our own ajax events which will still be handled by WC_AJAX::do_wc_ajax(). Because WC_AJAX::add_variation(),           //    6991
# WC_AJAX::link_all_variations() and WC_AJAX::save_variations() instantiates a variation with "new WC_Product_Variation" and will    //    6992
# not use our MC_Product_Variation.                                                                                                  //    6993
                                                                                                                                     //    6994
class MC_AJAX {                                                                                                                      //    6995
                                                                                                                                     //    6996
    public static function init() {                                                                                                  //    6997
        add_action( 'init', 'MC_AJAX::add_ajax_events' );                                                                            //    6998
    }                                                                                                                                //    6999
                                                                                                                                     //    7000
    # add_ajax_events() must run after WC_AJAX::add_ajax_events().                                                                   //    7001
                                                                                                                                     //    7002
    public static function add_ajax_events() {                                                                                       //    7003
                                                                                                                                     //    7004
        $ajax_events = [                                                                                                             //    7005
            'save_attributes'     => FALSE,                                                                                          //    7006
            'load_variations'     => FALSE,                                                                                          //    7007

### REDACTED lines 7008 -> 7012 redacted,      5 lines redacted. ###

        ];                                                                                                                           //    7013
                                                                                                                                     //    7014
        $my_ajax_events = [                                                                                                          //    7015
            'sv_load_variations_json' => TRUE,                                                                                       //    7016
        ];                                                                                                                           //    7017
                                                                                                                                     //    7018
        # Replace some WC_AJAX events handlers - remove_action() then add_action()                                                   //    7019
                                                                                                                                     //    7020
        foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                         //    7021
            # action [ 'WC_AJAX', $ajax_event ] has already been added since that is done when the Classic Commerce plugin           //    7022
            # is loaded so safe to remove on action 'init'                                                                           //    7023
            if ( ! remove_action( 'wp_ajax_woocommerce_' . $ajax_event, [ 'WC_AJAX', $ajax_event ] ) ) {                             //    7024
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //    7025
                                   "ERROR: MC_AJAX::add_ajax_events():replacement hook for \"wp_ajax_woocommerce_$ajax_event\" not " //    7026
                                       . "installed, original hook is: " . print_r( [ 'WC_AJAX', $ajax_event ], TRUE ),              //    7027
                                   'SV 0.1.0' );                                                                                     //    7028
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //    7029
                                   'ERROR: MC_AJAX::add_ajax_events():must be called after the hook is installed.', 'SV 0.1.0' );    //    7030
                break;                                                                                                               //    7031
            }                                                                                                                        //    7032
            add_action(    'wp_ajax_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                                       //    7033
            if ( $nopriv ) {                                                                                                         //    7034
                remove_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, [ 'WC_AJAX', $ajax_event ] );                            //    7035
                add_action(    'wp_ajax_nopriv_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                            //    7036
                remove_action( 'wc_ajax_' . $ajax_event, [ 'WC_AJAX', $ajax_event ] );                                               //    7037
                add_action(    'wc_ajax_' . $ajax_event, [ __CLASS__, $ajax_event ] );                                               //    7038
            }                                                                                                                        //    7039
        }   # foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                   //    7040
                                                                                                                                     //    7041
        # Add my AJAX handlers events                                                                                                //    7042
                                                                                                                                     //    7043
        foreach ( $my_ajax_events as $ajax_event => $nopriv ) {                                                                      //    7044
            add_action(    'wp_ajax_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                                       //    7045
            if ( $nopriv ) {                                                                                                         //    7046
                add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                               //    7047
                add_action( 'wc_ajax_' . $ajax_event,                    [ __CLASS__, $ajax_event ] );                               //    7048
            }                                                                                                                        //    7049
        }   # foreach ( $my_ajax_events as $ajax_event => $nopriv ) {                                                                //    7050
                                                                                                                                     //    7051
        # The WC_AJAX handlers unfortunately call wp_die() so they will never return. Instead of replacing the WC_AJAX handlers the  //    7052
        # following wraps the WC_AJAX handlers and postpones the call to wp_die() so the result of the AJAX handlers can be post     //    7053
        # processed.                                                                                                                 //    7054
                                                                                                                                     //    7055
        $ajax_events = [                                                                                                             //    7056
            'save_variations' => FALSE                                                                                               //    7057
        ];                                                                                                                           //    7058
                                                                                                                                     //    7059
        foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                         //    7060
            MC_Hook_Wrapper::wrap_hook( "WC_AJAX::{$ajax_event}", [ __CLASS__, $ajax_event ],                                        //    7061
                    'wp_ajax_woocommerce_' . $ajax_event, [ 'WC_AJAX', $ajax_event ], TRUE );                                        //    7062
        }   # foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                   //    7063
                                                                                                                                     //    7064
    }   # public static function add_ajax_events() {                                                                                 //    7065
                                                                                                                                     //    7066
                                                                                                                                     //    7067
    public static function save_attributes() {                                                                                       //    7069
        if ( ! MC_Simple_Variation_Functions::is_simple_variable( absint( $_POST['post_id'] ) )                                      //    7070
                && strpos( $_POST['data'], '&attribute_for_simple_variation%5B' ) === FALSE ) {                                      //    7071
            return WC_AJAX::save_attributes();                                                                                       //    7072
        }                                                                                                                            //    7073
        # Do attributes of a Simple Variable Product                                                                                 //    7074
        self::sv_save_attributes();                                                                                                  //    7075
    }   # public static function save_attributes() {                                                                                 //    7076
                                                                                                                                     //    7077
    public static function load_variations() {                                                                                       //    7079
        if ( ! MC_Simple_Variation_Functions::is_simple_variable( $_POST['product_id'] ) ) {                                         //    7080
            return WC_AJAX::load_variations();                                                                                       //    7081
        }                                                                                                                            //    7082
        # Do variations of a Simple Variable Product                                                                                 //    7083
        self::sv_load_variations();                                                                                                  //    7084
    }   # public static function load_variations() {                                                                                 //    7085
                                                                                                                                     //    7086
    private static function sv_save_attributes() {                                                                                   //    7087
        # The response to this AJAX action 'woocommerce_save_attributes' will trigger an AJAX action                                 //    7089
        # 'woocommerce_load_variations' which will call self::sv_load_variations()                                                   //    7090
        if ( MC_Simple_Variation_Functions::is_simple_variable( ( $post_id = absint( $_POST['post_id'] ) ) )                         //    7091
                || strpos( $_POST['data'], '&attribute_for_simple_variation%5B' ) !== FALSE ) {                                      //    7092
            parse_str( $_POST['data'], $data );                                                                                      //    7093
            MC_Simple_Variation_Functions::prepare_request_product_attributes( $data, $post_id );                                    //    7095
            $_POST['data'] = http_build_query( $data );                                                                              //    7096
            # Return an empty function so die() is prevented.                                                                        //    7097
            # add_filter( 'wp_die_ajax_handler', function( $die_handler ) {                                                          //    7098
            #     return function( $message, $title, $args ) {};                                                                     //    7099
            # } );                                                                                                                   //    7100
            $die_call = &MC_Utility::postpone_wp_die();                                                                              //    7101
            # WC_AJAX::save_attributes() will return since die() will not be called because of the 'wp_die_ajax_handler' filter.     //    7102
            WC_AJAX::save_attributes();                                                                                              //    7103
            $product    = wc_get_product( $post_id );                                                                                //    7104
            $data_store = WC_Data_Store::load( 'product-variation' );                                                                //    7105
            $data_store->canonicalize_variations( $product );                                                                        //    7106
            if ( MC_Product_Data_Store_CPT::is_virtual_variable( $post_id )                                                          //    7107
                    && ! MC_Product_Data_Store_CPT::check_count_of_virtual_variations( $post_id, $count ) ) {                        //    7108
            }                                                                                                                        //    7109
            # Must call _ajax_wp_die_handler() directly since we have installed a 'wp_die_ajax_handler' filter.                      //    7110
            # _ajax_wp_die_handler( '', '', [ 'response' => NULL ] );                                                                //    7111
            MC_Utility::do_postponed_wp_die( $die_call );                                                                            //    7112
        }                                                                                                                            //    7113
    }   # private static function sv_save_attributes() {                                                                             //    7114
                                                                                                                                     //    7115
    # sv_load_variations() loads the base variations of a simple variable product. It replaces the Classic Commerce                  //    7116
    # load_variations() for simple variable products.                                                                                //    7117
    private static function sv_load_variations( $ajax = TRUE, $product_id = NULL ) {                                                 //    7118
        global $post;                                                                                                                //    7126
        global $wpdb;                                                                                                                //    7127
                                                                                                                                     //    7128
        # Check permissions again and make sure we have what we need                                                                 //    7129
        if ( ! current_user_can( 'edit_products' ) ) {                                                                               //    7130
            die( -1 );                                                                                                               //    7131
        }                                                                                                                            //    7132
        if ( $ajax ) {                                                                                                               //    7133
            check_ajax_referer( 'load-variations', 'security' );                                                                     //    7134
            if ( empty( $_POST['product_id'] ) ) {                                                                                   //    7135
                die( -1 );                                                                                                           //    7136
            }                                                                                                                        //    7137
        } else {                                                                                                                     //    7138
            ob_start();                                                                                                              //    7139
        }                                                                                                                            //    7140
        $product_id   = $ajax ? absint( $_POST['product_id'] ) : absint( $product_id );                                              //    7141
        if ( MC_Simple_Variation_Functions::simple_variable_is_synchronizing( $product_id ) ) {                                      //    7142
?>                                                                                                                                   <!--  7143 -->
<div class="toolbar toolbar-top toolbar-simple_variation" style="background-color:#fff">                                             <!--  7144 -->
Components panel is not available until synchronization has completed.                                                               <!--  7145 -->
</div>                                                                                                                               <!--  7146 -->
<?php                                                                                                                                //    7147
            if ( ! $ajax ) {                                                                                                         //    7148
                $contents = ob_get_contents();                                                                                       //    7149
                ob_end_clean();                                                                                                      //    7150
                return $contents;                                                                                                    //    7151
            }                                                                                                                        //    7152
            die();                                                                                                                   //    7153
        }                                                                                                                            //    7154
        $loop         = '';                                                                                                          //    7155
        $variation_id = '';                                                                                                          //    7156
        $post         = get_post( $product_id );   # Set $post global so its available like within the admin screens                 //    7157
        $product      = wc_get_product( $product_id );                                                                               //    7158
?>                                                                                                                                   <!--  7159 -->
<div class="toolbar toolbar-top toolbar-simple_variation" style="background-color:#fff">                                             <!--  7160 -->
    &nbsp;                                                                                                                           <!--  7161 -->
<?php                                                                                                                                //    7162
        if ( PHP_INT_MAX >= 9223372036854775807 ) {                                                                                  //    7163
?>                                                                                                                                   <!--  7164 -->
    <input type="checkbox" name="mc_xii_virtual_variations"                                                                          <!--  7165 -->
        <?php echo MC_Product_Data_Store_CPT::is_virtual_variable( $product_id ) ? ' checked' : ''; ?>>                              <!--  7166 -->
    Use virtual composite products                                                                                                   <!--  7167 -->
    <input type="hidden" name="mc_xii_virtual_variations_1"                                                                          <!--  7168 -->
        value="<?php echo MC_Product_Data_Store_CPT::is_virtual_variable( $product_id ) ? 'on' : 'off'; ?>">                         <!--  7169 -->
<?php                                                                                                                                //    7170
        }                                                                                                                            //    7171
?>                                                                                                                                   <!--  7172 -->
    <div class="variations-pagenav">                                                                                                 <!--  7173 -->
        <span class="expand-close">                                                                                                  <!--  7174 -->
           (<a href="#" class="expand_all"><?php echo ucfirst( MC_Simple_Variation_Functions::$expand_label ); ?></a> /              <!--  7175 -->
            <a href="#" class="close_all" ><?php echo ucfirst( MC_Simple_Variation_Functions::$close_label );  ?></a>)               <!--  7176 -->
        </span>                                                                                                                      <!--  7177 -->
    </div>                                                                                                                           <!--  7178 -->
</div>                                                                                                                               <!--  7179 -->
<?php                                                                                                                                //    7180
        # $data_store = WC_Data_Store::load( 'product-variable' ); This does not work as load() returns a wrapper for                //    7181
        # the MC_Product_Variable_Data_Store_CPT object and get_base_variations_for_attributes() is resolved by                      //    7182
        # WC_Data_Store::__call() which cannot handle parameters passed by reference. The second, third and fourth parameters to     //    7183
        # get_base_variations_for_attributes() should be passed by reference.                                                        //    7184
        $data_store                   = new MC_Product_Variable_Data_Store_CPT();                                                    //    7185
        $base_variation_for_attribute = $data_store->get_base_variations_for_attributes( $product->get_id(), $attributes,            //    7186
                                            $attribute_values, $map_attributes_to_variation );                                       //    7187
                                                                                                                                     //    7188
        # below extracted from WC_Meta_Box_Product_Data::output_variations( )                                                        //    7189
                                                                                                                                     //    7190
        $variations_count       = absint( $wpdb->get_var( $wpdb->prepare( <<<EOD                                                     //    7191
SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'                                        //    7192
    AND post_status IN ( 'publish', 'private' )                                                                                      //    7193
EOD                                                                                                                                  //    7194
                                          , $post->ID ) ) );                                                                         //    7195
        $variations_per_page    = absint( apply_filters( 'woocommerce_admin_meta_boxes_variations_per_page', 15 ) );                 //    7196
        $variations_total_pages = ceil( $variations_count / $variations_per_page );                                                  //    7197
        $data_store             = $product->get_data_store();                                                                        //    7198
        $variation_counts       = $data_store->get_variation_counts( $product->get_id() );                                           //    7199
?>                                                                                                                                   <!--  7200 -->
<div class="toolbar mc_xii-simple_variations_top">                                                                                   <!--  7201 -->
<?php                                                                                                                                //    7202
        foreach ( $variation_counts['counts_by_attributes'] as $attribute => $count ) {                                              //    7203
            if ( in_array( $attribute, $variation_counts['optional'] ) ) {                                                           //    7204
                printf( MC_Simple_Variation_Functions::$there_are_variations_for_optional, $count, $attribute );                     //    7205
            } else {                                                                                                                 //    7206
                printf( MC_Simple_Variation_Functions::$there_are_variations_for_component, $count, $attribute );                    //    7207
            }                                                                                                                        //    7208
        }                                                                                                                            //    7209
        printf( MC_Simple_Variation_Functions::$from_these_base_will_be_generated,                                                   //    7210
                $variation_counts[ MC_Simple_Variation_Functions::TYPE_BASE ],                                                       //    7211
                $variation_counts[ MC_Simple_Variation_Functions::TYPE_COMPOUND ] );                                                 //    7212
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $product_id ) ) {                                                     //    7213
            if  ( $cumulative = get_post_meta( $product_id, '_mc_xii_prev_cumulative_elapsed_time', TRUE ) ) {                       //    7214
                printf( MC_Simple_Variation_Functions::$the_last_complete_update_used, number_format( $cumulative[0], 2),            //    7215
                        $cumulative[1], $cumulative[2], $cumulative[3] );                                                            //    7216
            }                                                                                                                        //    7217
        }                                                                                                                            //    7218
?>                                                                                                                                   <!--  7219 -->
</div>                                                                                                                               <!--  7220 -->
<?php                                                                                                                                //    7221
        # do the base variations grouped by their attribute                                                                          //    7222
        $loop           = 0;                                                                                                         //    7223
        $product_object = wc_get_product( $product_id );                                                                             //    7224
        foreach ( $attributes as $slug => $the_attribute ) {                                                                         //    7226
            $attribute_name = $the_attribute[ 'name' ];                                                                              //    7227
            if ( MC_Simple_Variation_Functions::is_optional_attribute( $slug, $product_id, $product_object ) ) {                     //    7228
                $attribute_name = MC_Simple_Variation_Functions::remove_optional_suffix( $attribute_name );                          //    7229
            }                                                                                                                        //    7230
            $variations_for_attribute = $base_variation_for_attribute[ $slug ];                                                      //    7231
?>                                                                                                                                   <!--  7232 -->
<div class="woocommerce_variation wc-metabox closed">                                                                                <!--  7233 -->
    <h3>                                                                                                                             <!--  7234 -->
        <div class="handlediv" title="<?php echo esc_attr( ucfirst( MC_Simple_Variation_Functions::$click_to_toggle ) ); ?>"></div>  <!--  7235 -->
        <?php echo '<strong>' . $attribute_name . '</strong>'; ?>                                                                    <!--  7236 -->
    </h3>                                                                                                                            <!--  7237 -->
    <div class="woocommerce_variable_attributes wc-metabox-content" style="display: none;">                                          <!--  7238 -->
        <div class="data" style="background-color:#f1f1f1;">                                                                         <!--  7239 -->
<?php                                                                                                                                //    7240
            # below extracted from html-variation-admin.php                                                                          //    7241
            $options = wc_get_text_attributes( $the_attribute['value'] );                                                            //    7242
            foreach ( $options as $option ) {                                                                                        //    7243
                if ( $option === MC_Simple_Variation_Functions::UNSELECTED ) {                                                       //    7244
                    continue;                                                                                                        //    7245
                }                                                                                                                    //    7246
                ++$loop;                                                                                                             //    7247
                $variation    = $variations_for_attribute[ $option ];                                                                //    7248
                $variation_id = $variation instanceof WC_Product_Variation ? $variation->get_id() : $variation['id'];                //    7249
                self::load_variations_pane_header( $loop, $variation_id, $attributes, $the_attribute, $option );                     //    7250
                $variation_object = $variation instanceof WC_Product_Variation ? $variation                                          //    7251
                                                                               : new WC_Product_Variation( $variation['id'] );       //    7252
                $variation_id     = $variation_object->get_id();                                                                     //    7253
                $variation        = get_post( $variation_id );                                                                       //    7254
                $variation_data   = array_merge( array_map( 'maybe_unserialize', get_post_custom( $variation_id ) ),                 //    7255
                                                 wc_get_product_variation_attributes( $variation_id ) );   # kept for BW compat.     //    7256
                ob_start( function( $buffer ) {                                                                                      //    7257
                    $buffer = preg_replace( [ '#^.+?</h3>#s', '#</div>\s*$#' ], '', $buffer );                                       //    7258
                    return $buffer;                                                                                                  //    7259
                });                                                                                                                  //    7260
                include( plugin_dir_path( __DIR__ ) . MC_Simple_Variation_Functions::$classic_commerce                               //    7261
                         . '/includes/admin/meta-boxes/views/html-variation-admin.php' );                                            //    7262
                ob_end_flush();                                                                                                      //    7263
?>                                                                                                                                   <!--  7264 -->
</div>                                                                                                                               <!--  7265 -->
<?php                                                                                                                                //    7266
            }   # foreach ( $options as $option ) {                                                                                  //    7267
            # above extracted from html-variation-admin.php                                                                          //    7268
?>                                                                                                                                   <!--  7269 -->
        </div>                                                                                                                       <!--  7270 -->
    </div>                                                                                                                           <!--  7271 -->
</div>                                                                                                                               <!--  7272 -->
<?php                                                                                                                                //    7273
        }   # foreach ( $attributes as $slug => $the_attribute ) {                                                                   //    7274
                                                                                                                                     //    7275
        # above extracted from WC_Meta_Box_Product_Data::output_variations()                                                         //    7276
                                                                                                                                     //    7277
        if ( ! $ajax ) {                                                                                                             //    7278
            $contents = ob_get_contents();                                                                                           //    7279
            ob_end_clean();                                                                                                          //    7280
            return $contents;                                                                                                        //    7281
        }                                                                                                                            //    7282
                                                                                                                                     //    7283
        die();                                                                                                                       //    7284
    }   # public static function sv_load_variations( $ajax = TRUE, $product_id = NULL ) {                                            //    7285
                                                                                                                                     //    7286
    # load_variations_pane_header() is a helper function for sv_load_variations()                                                    //    7287
                                                                                                                                     //    7288
    private static function load_variations_pane_header( $loop, $variation_id, $attributes, $attribute, $option ) {                  //    7289
?>                                                                                                                                   <!--  7290 -->
<div class="woocommerce_variation wc-metabox closed">                                                                                <!--  7291 -->
  <h3>                                                                                                                               <!--  7292 -->
<?php                                                                                                                                //    7293
        foreach ( $attributes as $alt_attribute ) {                                                                                  //    7294
            echo '<input type="hidden" name="attribute_' . sanitize_title( $alt_attribute['name'] ) . '[' . $loop . ']" value="'     //    7295
                    . ( $alt_attribute[ 'name' ] === $attribute[ 'name' ] ? esc_attr( $option )                                      //    7296
                                                                          : MC_Simple_Variation_Functions::UNSELECTED ) . '">';      //    7297
        }                                                                                                                            //    7298
?>                                                                                                                                   <!--  7299 -->
    <div class="handlediv" title="<?php echo esc_attr( ucfirst( MC_Simple_Variation_Functions::$click_to_toggle ) ); ?>"></div>      <!--  7300 -->
    <input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />           <!--  7301 -->
    <!-- below is not used by simple variations but necessary to prevent undefined index errors in                                   <!--  7302 -->
        classic-commerce\includes\admin\meta-boxes\class-wc-meta-box-product-data.php -->                                            <!--  7303 -->
    <input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="" />                  <!--  7304 -->
<?php                                                                                                                                //    7305
        echo '<strong>#' . esc_html( $variation_id ) . '</strong>&nbsp;&nbsp;';                                                      //    7306
        echo '<strong>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</strong>';                   //    7307
?>                                                                                                                                   <!--  7308 -->
  </h3>                                                                                                                              <!--  7309 -->
<?php                                                                                                                                //    7310
    }   # private static function load_variations_pane_header( $loop, $variation_id, $attributes, $attribute, $option ) {            //    7311
                                                                                                                                     //    7312
    # sv_load_variations_json() services requests from React Redux middleware.                                                       //    7313
                                                                                                                                     //    7314
    public static function sv_load_variations_json() {                                                                               //    7316
        global $wpdb;                                                                                                                //    7317
        if ( empty( $_REQUEST['product_id'] ) || ! wc_get_product( absint( $_REQUEST['product_id'] ) ) ) {                           //    7318
            wp_send_json( new stdClass() );                                                                                          //    7320
        }                                                                                                                            //    7321
        $wpdb->query( 'SET SQL_BIG_SELECTS=1' );                                                                                     //    7322
        $product_id    = absint( $_REQUEST[ 'product_id' ] );                                                                        //    7323
        $title         = $wpdb->get_col( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d", $product_id ) );       //    7324
        $variation_ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                      //    7325
SELECT ID FROM $wpdb->posts p, $wpdb->postmeta m WHERE p.ID = m.post_id                                                              //    7326
    AND p.post_parent = %d AND p.post_status = 'publish' AND m.meta_key = '_mc_xii_variation_type' AND m.meta_value = 'base'         //    7327
EOD                                                                                                                                  //    7328
                                                             , $product_id ) );                                                      //    7329
        $variation_ids = implode( ', ', $variation_ids );                                                                            //    7330
        $unselected    = MC_Simple_Variation_Functions::UNSELECTED;                                                                  //    7331
        $query = <<<EOD                                                                                                              //    7332
SELECT m_attribute.post_id id, m_attribute.meta_key attribute, m_attribute.meta_value selection, m_price.meta_value price,           //    7333
        m_quantity.meta_value quantity, m_image.meta_value image_id, m_description.meta_value description                            //    7334
    FROM $wpdb->postmeta m_attribute, $wpdb->postmeta m_price, $wpdb->postmeta m_quantity,                                           //    7335
            $wpdb->postmeta m_image, $wpdb->postmeta m_description                                                                   //    7336
    WHERE m_attribute.post_id in ( $variation_ids )                                                                                  //    7337
        AND m_price.post_id        = m_attribute.post_id                                                                             //    7338
        AND m_quantity.post_id     = m_attribute.post_id                                                                             //    7339
        AND m_image.post_id        = m_attribute.post_id                                                                             //    7340
        AND m_description.post_id  = m_attribute.post_id                                                                             //    7341
        AND m_attribute.meta_key LIKE 'attribute\\_%' AND m_attribute.meta_value != '$unselected'                                    //    7342
        AND m_price.meta_key       = '_price'                                                                                        //    7343
        AND m_price.meta_value    != ''                                                                                              //    7344
        AND m_quantity.meta_key    = '_stock'                                                                                        //    7345
        AND m_quantity.meta_value != ''                                                                                              //    7346
        AND m_image.meta_key       = '_thumbnail_id'                                                                                 //    7347
        AND m_description.meta_key = '_variation_description'                                                                        //    7348
EOD;                                                                                                                                 //    7349
        $results = $wpdb->get_results( $query, OBJECT_K );                                                                           //    7351
        if ( empty( $results ) ) {                                                                                                   //    7353
            if ( $wpdb->use_mysqli ) {                                                                                               //    7354
                $error = mysqli_error( $wpdb->dbh );                                                                                 //    7355
            } else {                                                                                                                 //    7356
                $error = mysql_error( $wbdb->dbh );                                                                                  //    7357
            }                                                                                                                        //    7358
            # wp_send_json( $error );                                                                                                //    7361
            # $error is:                                                                                                             //    7362
            # The SELECT would examine more than MAX_JOIN_SIZE rows;                                                                 //    7363
            # check your WHERE and use SET SQL_BIG_SELECTS=1 or SET MAX_JOIN_SIZE=# if the SELECT is okay                            //    7364
            # $max_join_size = $wpdb->get_results( 'SHOW VARIABLES LIKE "MAX_JOIN_SIZE"' );                                          //    7365
            # wp_send_json( $max_join_size );                                                                                        //    7367
            # but $max_join_size is:                                                                                                 //    7368
            # [{"Variable_name":"max_join_size","Value":"4000000"}]                                                                  //    7369
            # $sql_big_selects = $wpdb->get_results( 'SHOW VARIABLES LIKE "SQL_BIG_SELECTS"' );                                      //    7370
            # wp_send_json( $sql_big_selects );                                                                                      //    7372
            # sql_big_selects is:                                                                                                    //    7373
            # [{"Variable_name":"sql_big_selects","Value":"OFF"}]                                                                    //    7374
            # $wpdb->query( 'SET SQL_BIG_SELECTS=1' );                                                                               //    7375
            # $sql_big_selects = $wpdb->get_results( 'SHOW VARIABLES LIKE "SQL_BIG_SELECTS"' );                                      //    7376
            # wp_send_json( $sql_big_selects );                                                                                      //    7378
            # now sql_big_selects is:                                                                                                //    7379
            # [{"Variable_name":"sql_big_selects","Value":"ON"}]                                                                     //    7380
            # But the first $wpdb->get_results() still fails!                                                                        //    7381
            $results = $wpdb->get_results( <<<EOD                                                                                    //    7382
SELECT post_id id, meta_key attribute, meta_value selection FROM $wpdb->postmeta                                                     //    7383
    WHERE post_id in ( $variation_ids ) AND meta_key LIKE 'attribute\\_%' AND meta_value != '$unselected'                            //    7384
EOD                                                                                                                                  //    7385
                                           , OBJECT_K );                                                                             //    7386
            $fields = [                                                                                                              //    7387
                (object) [ 'key' => '_price',                 'name' => 'price'    ],                                                //    7388
                (object) [ 'key' => '_stock',                 'name' => 'quantity' ],                                                //    7389
                (object) [ 'key' => '_thumbnail_id',          'name' => 'image_id' ],                                                //    7390
                (object) [ 'key' => '_variation_description', 'name' => 'description' ]                                              //    7391
            ];                                                                                                                       //    7392
            $field_results = self::join_post_meta( $variation_ids, $fields );                                                        //    7393
            foreach ( $field_results as $id => $field_result ) {                                                                     //    7394
                $field_result->id        = $results[ $id ]->id;                                                                      //    7395
                $field_result->attribute = $results[ $id ]->attribute;                                                               //    7396
                $field_result->selection = $results[ $id ]->selection;                                                               //    7397
            }                                                                                                                        //    7398
            $results = $field_results;                                                                                               //    7399
        }                                                                                                                            //    7401
        foreach ( $results as $id => $result ) {                                                                                     //    7402
            $result->product_name = $title[0];                                                                                       //    7403
            if ( is_array( $full_size_image = wp_get_attachment_image_src( $result->image_id, 'full' ) ) && $full_size_image ) {     //    7404
                $result->full_size_image        = $full_size_image[0];                                                               //    7405
                $result->full_size_image_width  = $full_size_image[1];                                                               //    7406
                $result->full_size_image_height = $full_size_image[2];                                                               //    7407
            }                                                                                                                        //    7408
            if ( is_array( $thumbnail = wp_get_attachment_image_src( $result->image_id, 'shop_thumbnail' ) ) && $thumbnail ) {       //    7409
                $result->thumbnail = $thumbnail[0];                                                                                  //    7410
            }                                                                                                                        //    7411
            if ( ! empty( $_REQUEST[ 'image_props' ] ) ) {                                                                           //    7412
                $result->image_props = wc_get_product_attachment_props( $result->image_id );                                         //    7413
            }                                                                                                                        //    7414
        }                                                                                                                            //    7415
        wp_send_json( $results );                                                                                                    //    7417
    }   # public static function sv_load_variations_json() {                                                                         //    7418
                                                                                                                                     //    7419
    # join_post_meta() is a helper function for sv_load_variations_json().                                                           //    7420
                                                                                                                                     //    7421
    private static function join_post_meta( $ids, $fields ) {                                                                        //    7422
        global $wpdb;                                                                                                                //    7423
        $ids = is_array( $ids ) ? implode( ', ', $ids ) : $ids;                                                                      //    7424
        $results = [];                                                                                                               //    7425
        foreach ( $fields as $field ) {                                                                                              //    7426
            $values = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                     //    7427
SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND post_id in ( $ids )                                          //    7428
EOD                                                                                                                                  //    7429
                                                        , $field->key ), OBJECT );                                                   //    7430
            foreach ( $values as $value ) {                                                                                          //    7431
                $results[ $value->post_id ][ $field->name ] = $value->meta_value;                                                    //    7432
            }                                                                                                                        //    7433
        }                                                                                                                            //    7434
        $results = array_map( function( $result ) {                                                                                  //    7435
            return (object) $result;                                                                                                 //    7436
        }, $results );                                                                                                               //    7437
        return $results;                                                                                                             //    7438
    }   # private static function join_post_meta( $ids, $fields ) {                                                                  //    7439
                                                                                                                                     //    7440

### REDACTED lines 7441 -> 7457 redacted,     17 lines redacted. ###

                                                                                                                                     //    7458
    # MC_AJAX::save_variations() is a wrapper for WC_AJAX::save_variations() installed with MC_Hook_Wrapper::wrap_hook().            //    7459
    # This is tricky as WC_AJAX::save_variations() calls wp_die() and does not return. However, using MC_Utility::postpone_wp_die()  //    7460
    # we can get WC_AJAX::save_variations() to return and then do post processing after the call.                                    //    7461
                                                                                                                                     //    7462
    public static function save_variations( $callback ) {                                                                            //    7464
        # $callback should be  WC_AJAX::save_variations()                                                                            //    7465
        check_ajax_referer( 'save-variations', 'security' );                                                                         //    7466
        if ( ! current_user_can( 'edit_products' ) || empty( $_POST ) || empty( $_POST['product_id'] ) ) {                           //    7467
            wp_die( -1 );                                                                                                            //    7468
        }                                                                                                                            //    7469
        $old_manage_stock = NULL;                                                                                                    //    7470
        $new_manage_stock = NULL;                                                                                                    //    7471
        if ( isset( $_POST['variable_post_id'] ) ) {                                                                                 //    7472
            $max_loop = max( array_keys( $_POST['variable_post_id'] ) );                                                             //    7473
            for ( $i = 0; $i <= $max_loop; $i++ ) {                                                                                  //    7474
                if ( ! isset( $_POST['variable_post_id'][ $i ] ) ) {                                                                 //    7475
                  continue;                                                                                                          //    7476
                }                                                                                                                    //    7477
                $_POST['variable_shipping_class'][$i] = '-1';                                                                        //    7478
                $_POST['variable_tax_class'][$i]      = 'parent';                                                                    //    7479
            }                                                                                                                        //    7480
            if ( isset( $_POST['variable_post_id'] ) ) {                                                                             //    7481
                $index = min( array_keys( $_POST['variable_post_id'] ) );                                                            //    7482
                $new_manage_stock = isset( $_POST['variable_manage_stock'][ $index ] );                                              //    7483
                $variation_id = absint( $_POST['variable_post_id'][ $index ] );                                                      //    7484
                $old_manage_stock = wc_string_to_bool( get_post_meta( $variation_id, '_manage_stock', TRUE ) );                      //    7485
            }                                                                                                                        //    7486
        }                                                                                                                            //    7487
        # $callback is WC_AJAX::save_variations() which normally exits by calling wp_die()                                           //    7488
        $die_call = &MC_Utility::postpone_wp_die();                                                                                  //    7489
        call_user_func( $callback );                                                                                                 //    7490
        # called via AJAX action 'wp_ajax_woocommerce_[nopriv_]save_variations'                                                      //    7491
        # called by WC_AJAX::save_variations()                                                                                       //    7492
        # WC_AJAX::save_variations() will also be called when the post is saved                                                      //    7493
        #     - see wc_meta_boxes_product_variations_ajax.save_on_submit() in meta-boxes-product-variation.js                        //    7494
        # WC_AJAX::save_variations() calls WC_Meta_Box_Product_Data::save_variations()                                               //    7495
        # WC_Meta_Box_Product_Data::save_variations() does $variation = new WC_Product_Variation(); $variation->set_props();         //    7496
        #     $variation->save();                                                                                                    //    7497
        # consider using action 'woocommerce_save_product_variation' in WC_Meta_Box_Product_Data::save_variations()                  //    7498
        global $wpdb;                                                                                                                //    7499
        $id = absint( $_POST['product_id'] );                                                                                        //    7500
        if ( self::is_simple_variable( $id ) ) {                                                                                     //    7501
            MC_Product_Data_Store_CPT::update_virtual_variable_product_attributes_version( $id );                                    //    7502
            # Update the compound variations of a Simple Variable Product                                                            //    7503
            MC_Simple_Variation_Functions::sync_compound_variations_with_base_variations( $id );                                     //    7504
            if ( isset( $_POST['variable_post_id'] ) && is_array( $_POST['variable_post_id'] )                                       //    7505
                && isset( $_POST['variable_manage_stock'][ min( array_keys( $_POST['variable_post_id'] ) ) ] ) ) {                   //    7506
                # Below is necessary as WC_Post_Data::WC_Product::deferred_product_sync() does not handle the following properties.  //    7507
                # See wc_deferred_product_sync( $this->get_parent_id() )                                                             //    7508
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = '" . wc_bool_to_string( FALSE )                               //    7509
                                  . "' WHERE post_id = $id AND meta_key = '_manage_stock'" );                                        //    7510
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = NULL WHERE post_id = $id AND meta_key = '_stock'" );          //    7511
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = 'instock'"                                                    //    7512
                                  . " WHERE post_id = $id AND meta_key = '_stock_status'" );                                         //    7513
            }                                                                                                                        //    7514
            if ( $new_manage_stock && ! $old_manage_stock ) {                                                                        //    7515
                $now = new WC_DateTime();                                                                                            //    7516
                update_post_meta( $id, '_mc_xii_variation_manage_stock', "$id:" . $now->getTimestamp() );                            //    7517
            } else if ( ! $new_manage_stock && $old_manage_stock ) {                                                                 //    7518
                delete_post_meta( $id, '_mc_xii_variation_manage_stock' );                                                           //    7519
            }                                                                                                                        //    7520
        }                                                                                                                            //    7521
        MC_Utility::do_postponed_wp_die( $die_call );                                                                                //    7522
    }                                                                                                                                //    7523
                                                                                                                                     //    7524
}   # class MC_AJAX {                                                                                                                //    7525
                                                                                                                                     //    7526
MC_AJAX::init();                                                                                                                     //    7527

### REDACTED lines 7528 -> 7690 redacted,    163 lines redacted. ###

                                                                                                                                     //    7691
if ( MC_Simple_Variation_Functions::$load_demo_class ) {                                                                             //    7692
                                                                                                                                     //    7693
    class MC_Simple_Variation_Demo_Functions extends MC_Simple_Variation_Functions {                                                 //    7694
                                                                                                                                     //    7695
        const SAND_BOX                            = 'mc_xii_sandbox';                                                                //    7696
                                                                                                                                     //    7697

### REDACTED lines 7698 -> 7718 redacted,     21 lines redacted. ###

                                                                                                                                     //    7719
        public static function init() {                                                                                              //    7720
            global $wpdb;                                                                                                            //    7721
                                                                                                                                     //    7722

### REDACTED lines 7723 -> 7808 redacted,     86 lines redacted. ###

                                                                                                                                     //    7809
                MC_Utility::add_filter( 'woocommerce_login_redirect', 'mc_xii_woocommerce_login_redirect',                           //    7810
                        function( $redirect_to, $user ) {                                                                            //    7811
                    return self::login_redirect( $redirect_to, $user );                                                              //    7812
                }, 10, 2 );                                                                                                          //    7813
                                                                                                                                     //    7814
                MC_Utility::add_filter( 'login_redirect', 'mc_xii_login_redirect',                                                   //    7815
                        function( $redirect_to, $requested_redirect_to, $user ) {                                                    //    7816
                    return self::login_redirect( $redirect_to, $user );                                                              //    7817
                }, 10, 3 );                                                                                                          //    7818
                                                                                                                                     //    7819

### REDACTED lines 7820 -> 7857 redacted,     38 lines redacted. ###

                                                                                                                                     //    7858
            MC_Utility::add_action( 'admin_init', 'mc_xii_admin_init_demo_functions', function() use ( $demo_user_enabled ) {        //    7859
                                                                                                                                     //    7860

### REDACTED lines 7861 -> 7882 redacted,     22 lines redacted. ###

                                                                                                                                     //    7883
                if ( $demo_user_enabled && self::is_demo_user() ) {                                                                  //    7884
                    add_filter( 'submenu_file', 'MC_Simple_Variation_Demo_Functions::fix_admin_menu', 1 );                           //    7885
                                                                                                                                     //    7886

### REDACTED lines 7887 -> 7969 redacted,     83 lines redacted. ###

                                                                                                                                     //    7970
                    MC_Utility::add_action( 'add_admin_bar_menus', 'mc_xii_add_admin_bar_menus', function() {                        //    7971
                        MC_Utility::add_action( 'admin_bar_menu', 'mc_xii_admin_bar_menu', function( $wp_admin_bar ) {               //    7972
                            $wp_admin_bar->remove_node( 'new-content' );                                                             //    7974
                        }, PHP_INT_MAX, 1 );                                                                                         //    7975
                    } );                                                                                                             //    7976
                }   # if ( $demo_user_enabled && self::is_demo_user() ) {                                                            //    7977
                # 9 because this must run before WC_Admin::prevent_admin_access() which runs at the default 10                       //    7978
            }, 9 );   # MC_Utility::add_action( 'admin_init', 'mc_xii_admin_init_demo_functions', function() use ( $demo_user_enable //    7979
                                                                                                                                     //    7980
            MC_Utility::add_action( 'init', 'mc_xii_init_demo_functions', function() use ( $demo_user_enabled ) {                    //    7981
                                                                                                                                     //    7982
                if ( ! is_admin() ) {                                                                                                //    7983
                                                                                                                                     //    7984

### REDACTED lines 7985 -> 8026 redacted,     42 lines redacted. ###

                                                                                                                                     //    8027
                    # Block REST API requests for demo users.                                                                        //    8028
                    # See "add_action( 'parse_request', 'rest_api_loaded' );" in .../wp-includes/default-filters.php                 //    8029
                                                                                                                                     //    8030
                    MC_Utility::add_action( 'parse_request', 'mc_xii_parse_request', function() use ( $demo_user_enabled ) {         //    8031
                        if ( $demo_user_enabled && self::is_demo_user() && ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {   //    8037
                            wp_die( __( 'Sorry, REST API requests are not allowed for demo users.' ), 403 );                         //    8038
                        }                                                                                                            //    8039
                    }, 9 );                                                                                                          //    8040
                                                                                                                                     //    8041
                }   # if ( ! is_admin() ) {                                                                                          //    8042
                                                                                                                                     //    8043
                if ( ! is_admin() && $demo_user_enabled && self::is_demo_user() ) {                                                  //    8044
                    MC_Utility::add_filter( 'wp_page_menu', 'mc_xii_wp_page_menu', function( $menu ) {                               //    8045
                        static $count = 0;                                                                                           //    8046
                        # This filter called twice. Duplicate menus are emitted. Apparently, frontend code must be fixing this.      //    8050
                        # [01-Sep-2022 11:28:24 UTC] FILTER::wp_page_menu():$count = 1, crc32($menu) = 1584987908                    //    8051
                        # [01-Sep-2022 11:28:24 UTC] FILTER::wp_page_menu():$count = 2, crc32($menu) = 1584987908                    //    8052
                        # [01-Sep-2022 11:28:24 UTC] FILTER::wp_page_menu():BACKTRACE =                                              //    8053
                        # require('wp-blog-header.php')                                                                              //    8054
                        # require_once('wp-includes/template-loader.php')                                                            //    8055
                        # include('/plugins/classic-commerce/templates/single-product.php')                                          //    8056
                        # get_header                                                                                                 //    8057
                        # locate_template                                                                                            //    8058
                        # load_template                                                                                              //    8059
                        # require_once('/themes/storefront/header.php')                                                              //    8060
                        # do_action('storefront_header')                                                                             //    8061
                        # WP_Hook->do_action                                                                                         //    8062
                        # WP_Hook->apply_filters                                                                                     //    8063
                        # storefront_primary_navigation                                                                              //    8064
                        # wp_nav_menu                                                                                                //    8065
                        # wp_page_menu                                                                                               //    8066
                        # apply_filters('wp_page_menu')                                                                              //    8067
                        # WP_Hook->apply_filters                                                                                     //    8068
                        # Kwdb_::{closure}                                                                                           //    8069
                        if ( strpos( $menu, __( 'Log Out' ) ) !== FALSE ) {                                                          //    8070
                            return $menu;                                                                                            //    8071
                        }                                                                                                            //    8072
                        $menu = preg_replace_callback( '#<li.+?href="(.+?)".*?>(.+?)</a></li>#s', function( $matches ) {             //    8074
                            if ( $matches[ 2 ] === __( 'Home', 'classic-commerce' ) ) {                                              //    8075
                                return $matches[ 0 ];                                                                                //    8076
                            }                                                                                                        //    8077
                            $includes = [ '/cart/', '/checkout/', '/my-account/', '/shop/' ];                                        //    8078
                            if ( preg_match( '#/[^/]+/$#', $matches[ 1 ], $matches_2 ) == 1 ) {                                      //    8079
                                return in_array( $matches_2[ 0 ], $includes ) ? $matches[ 0 ] : "<!-- {$matches[ 0 ]} -->";          //    8080
                            }                                                                                                        //    8081
                            return "<!-- {$matches[ 0 ]} -->";                                                                       //    8082
                        }, $menu );                                                                                                  //    8083
                        $id = self::$demo_product_id;                                                                                //    8084
                        if ( $id && preg_match( '#<li.*><a\shref="(.+?)">Home</a></li>#', $menu, $matches ) ) {                      //    8085
                            $host = $matches[ 1 ];                                                                                   //    8087
                            $menu = preg_replace( '#</ul></div>#',                                                                   //    8088
                                                  '<li class="page_item">'                                                           //    8089
                                                    . "<a href=\"{$host}wp-admin/post.php?post={$id}&action=edit\">"                 //    8090
                                                          . __( 'Edit product', 'classic-commerce' )                                 //    8091
                                                    . '</a>'                                                                         //    8092
                                                . '</li>'                                                                            //    8093
                                                . '<li class="page_item">'                                                           //    8094
                                                    . '<a href="' . esc_url( wp_logout_url() ) . '">'                                //    8095
                                                          . __( 'Log Out' )                                                          //    8096
                                                    . '</a>'                                                                         //    8097
                                                . '</li>'                                                                            //    8098
                                            . '</ul></div>',                                                                         //    8099
                                                  $menu );                                                                           //    8100
                        }   # if ( $id && preg_match( '#<li.*><a\shref="(.+?)">Home</a></li>#', $menu, $matches ) ) {                //    8101
                        return $menu;                                                                                                //    8102
                    } );   # MC_Utility::add_filter( 'wp_page_menu', 'mc_xii_wp_page_menu', function( $menu ) {                      //    8103
                }   # if ( ! is_admin() && $demo_user_enabled && self::is_demo_user() ) {                                            //    8104
            } );   # MC_Utility::add_action( 'init', 'mc_xii_init_demo_functions', function() use ( $demo_user_enabled ) {           //    8105
                                                                                                                                     //    8106

### REDACTED lines 8107 -> 8145 redacted,     39 lines redacted. ###

                                                                                                                                     //    8146
        }   # public static function init() {                                                                                        //    8147
                                                                                                                                     //    8148
        public static function is_demo_user( $user = NULL ) {                                                                        //    8149
            $user  = $user ? $user : wp_get_current_user();                                                                          //    8150
            if ( get_class( $user ) !== 'WP_User' ) {                                                                                //    8151
                return FALSE;                                                                                                        //    8152
            }                                                                                                                        //    8153
            $roles = $user->roles;                                                                                                   //    8154
            return $roles ? in_array( self::SAND_BOX, $roles ) : FALSE;                                                              //    8155
        }                                                                                                                            //    8156
                                                                                                                                     //    8157
        public static function login_redirect( $redirect_to, $user ) {                                                               //    8158
            if ( self::is_demo_user( $user ) ) {                                                                                     //    8159
                if ( $demo_product_id = get_user_meta( $user->ID, 'mc_xii_demo_product_id', TRUE ) ) {                               //    8160
                    self::$demo_product_id = $demo_product_id;                                                                       //    8161
                } else {                                                                                                             //    8162
                    self::$demo_product_id = 0;                                                                                      //    8163
                    # Must explicitly set the current user since wp_signon() does not set the current user.                          //    8164
                    wp_set_current_user( $user->ID );                                                                                //    8165
                    self::reset_demo_product( $user );                                                                               //    8166
                }                                                                                                                    //    8167
                return admin_url( 'post.php?post=' . self::$demo_product_id . '&action=edit' );                                      //    8168
            }                                                                                                                        //    8169
            return $redirect_to;                                                                                                     //    8170
        }                                                                                                                            //    8171
                                                                                                                                     //    8172

### REDACTED lines 8173 -> 8515 redacted,    343 lines redacted. ###

                                                                                                                                     //    8516
        public static function fix_admin_menu( $submenu_file ) {                                                                     //    8517
            global $menu;                                                                                                            //    8518
            global $submenu;                                                                                                         //    8519
            # Fix $menu and $submenu using side effects on this filter.                                                              //    8520
            unset( $menu[ 2 ], $menu[ 10 ], $menu[ 70 ] );                                                                           //    8523
            unset( $submenu[ 'edit.php?post_type=product' ] );                                                                       //    8524
            return $submenu_file;                                                                                                    //    8526
        }                                                                                                                            //    8527
    }   # class MC_Simple_Variation_Demo_Functions extends MC_Simple_Variation_Functions {                                           //    8528
                                                                                                                                     //    8529
    MC_Simple_Variation_Demo_Functions::init();                                                                                      //    8530
                                                                                                                                     //    8531
}   # if ( MC_Simple_Variation_Functions::$load_demo_class ) {                                                                       //    8532
                                                                                                                                     //    8533
                                                                                                                                     //    8938
# The wXy preprocessor removes all error_log() statements. So, if you really want to log something use xerror_log().                 //    8939
include_once dirname( __FILE__ ) . '/xerror_log.php';                                                                                //    8940
