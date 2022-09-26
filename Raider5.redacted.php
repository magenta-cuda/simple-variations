<?php                                                                                                                                //       0
/*                                                                                                                                   //       1
 * Plugin Name: Simple Variations for Classic Commerce                                                                               //       2
 * Plugin URI: http://svmc.x10host.com/                                                                                              //       3
 * Description: Implements the variations of a variable product as the Cartesian product of the attributes.                          //       4
 * Version: 0.1.0                                                                                                                    //       5
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
        $notices_to_do = count( $notices );                                                                                          //     178
        foreach ( $notices as $notice ) {                                                                                            //     179
            add_action( 'admin_notices', function() use ( $notice, &$notices_to_do ) {                                               //     180
                echo $notice;                                                                                                        //     181
                if ( ! --$notices_to_do ) {                                                                                          //     182
                    delete_transient( self::TRANSIENT_NOTICES );                                                                     //     183
                }                                                                                                                    //     184
            } );                                                                                                                     //     185
        }                                                                                                                            //     186
    }   # public static function do_transient_admin_notices() {                                                                      //     187
                                                                                                                                     //     188
    # If a notice is generated after the 'admin_notices' action has already been done then add the notice to the queue.              //     189
    # Also useful to queue admin notices while processing AJAX requests.                                                             //     190
    public static function do_admin_notice( $notice, $class = 'info', $force = FALSE ) {                                             //     191
        if ( $force || did_action( 'admin_notices' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {                                //     192
            self::add_transient_admin_notice( $notice, $class );                                                                     //     193
        } else {                                                                                                                     //     194
            add_action( 'admin_notices', function() use ( $notice, $class ) {                                                        //     195
?>                                                                                                                                   <!--   196 -->
<div class="notice notice-<?php echo $class; ?> is-dismissible">                                                                     <!--   197 -->
    <p><?php echo $notice; ?></p>                                                                                                    <!--   198 -->
</div>                                                                                                                               <!--   199 -->
<?php                                                                                                                                //     200
            } );                                                                                                                     //     201
        }                                                                                                                            //     202
    }   # public static function do_admin_notice( $notice, $class = 'info' ) {                                                       //     203
                                                                                                                                     //     204
    public static function sideload_image( $file_name, $desc = NULL, $post_data = [] ) {                                             //     205
        require_once(ABSPATH . 'wp-admin/includes/media.php');                                                                       //     206
        require_once(ABSPATH . 'wp-admin/includes/file.php');                                                                        //     207
        require_once(ABSPATH . 'wp-admin/includes/image.php');                                                                       //     208
        $slash_pos              = strrpos( $file_name, '/' );                                                                        //     209
        $file_array             = [];                                                                                                //     210
        $file_array['name']     = $slash_pos === FALSE ? $file_name : substr( $file_name, $slash_pos + 1 );                          //     211
        $file_array['tmp_name'] = wp_tempnam();                                                                                      //     212
        copy( plugin_dir_path( __FILE__ ) . $file_name, $file_array['tmp_name'] );                                                   //     213
        clearstatcache();                                                                                                            //     214
        return media_handle_sideload( $file_array, 0, $desc, $post_data );                                                           //     215
        # return media_handle_sideload( [ 'name' => $file_name, 'tmp_name' => plugin_dir_path( __FILE__ ) . $file_name ], 0 );       //     216
        # above will not work as media_handle_sideload() will unlink() the 'tmp_name' file                                           //     217
    }   # public static function sideload_image( $file_name, $desc = NULL, $post_data = [] ) {                                       //     218
                                                                                                                                     //     219
}   # class MC_Utility {                                                                                                             //     220
                                                                                                                                     //     221
# MC_Hook_Wrapper provides a paradigm for wrapping WordPress actions and filters. The difference between wrapping and WordPress      //     222
# priority based hooks is priority based hooks are called sequentially by priority but wrapped hooks are nested calls allowing       //     223
# processing before and after the call to the nested hook.                                                                           //     224
                                                                                                                                     //     225
class MC_Hook_Wrapper {                                                                                                              //     226
                                                                                                                                     //     227
    private $wrapper;                                                                                                                //     228
    private $callback;         # binds a callback to this object                                                                     //     229
    private $is_action;        # is this an action or filter                                                                         //     230
    private $priority;                                                                                                               //     231
    private $accepted_args;                                                                                                          //     232
                                                                                                                                     //     233
    public function __construct( $wrapper, $callback, $is_action = FALSE, $priority = 10, $accepted_args = 1 ) {                     //     234
        $this->wrapper       = $wrapper;                                                                                             //     235
        $this->callback      = $callback;                                                                                            //     236
        $this->is_action     = $is_action;                                                                                           //     237
        $this->priority      = $priority;                                                                                            //     238
        $this->accepted_args = $accepted_args;                                                                                       //     239
    }                                                                                                                                //     240
                                                                                                                                     //     241
    public function call_wrapper( ...$args ) {                                                                                       //     243
        return call_user_func_array( $this->wrapper, array_merge( [ $this->callback ], $args ) );                                    //     245
    }                                                                                                                                //     246
                                                                                                                                     //     247
    public function get_callback() {                                                                                                 //     248
        return $this->callback;                                                                                                      //     249
    }                                                                                                                                //     250
                                                                                                                                     //     251
    public function is_action() {                                                                                                    //     252
        return $this->is_action;                                                                                                     //     253
    }                                                                                                                                //     254
                                                                                                                                     //     255
    public function get_priority() {                                                                                                 //     256
        return $this->priority;                                                                                                      //     257
    }                                                                                                                                //     258
                                                                                                                                     //     259
    public function get_accepted_args() {                                                                                            //     260
        return $this->accepted_args;                                                                                                 //     261
    }                                                                                                                                //     262
                                                                                                                                     //     263
    # the $wrapper argument in wrap_hook() should be defined like this:                                                              //     264
    #                                                                                                                                //     265
    # $wrapper = function( $callback, ...$args ) {                                                                                   //     266
    #     ...                                                                                                                        //     267
    #     $ret = call_user_func_array( $callback, $args );                                                                           //     268
    #     ...                                                                                                                        //     269
    #     return $ret;                                                                                                               //     270
    # }                                                                                                                              //     271
                                                                                                                                     //     272
    public static function wrap_hook( $handle, $wrapper, $tags, $callback, $is_action = FALSE, $priority = 10,                       //     273
                                      $accepted_args = 1 ) {                                                                         //     274
        $tags = is_array( $tags ) ? $tags : [ $tags ];                                                                               //     275
        foreach ( $tags as $tag ) {                                                                                                  //     276
            # $wrapper_obj is an object which is bound to the original callback                                                      //     277
            $wrapper_obj          = new self( $wrapper, $callback, $is_action, $priority, $accepted_args );                          //     278
            if ( ! remove_filter( $tag, $callback, $priority ) ) {                                                                   //     279
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //     280
                                   "ERROR: MC_Hook_Wrapper::wrap_hook():hook for \"$tag\" not installed, callback is: "              //     281
                                       . print_r( $callback, TRUE ), 'SV 0.1.0' );                                                   //     282
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //     283
                                   'ERROR: MC_Hook_Wrapper::wrap_hook():must be called after the hook is installed.', 'SV 0.1.0' );  //     284
                return FALSE;                                                                                                        //     285
            }                                                                                                                        //     286
            if ( $is_action ) {                                                                                                      //     287
                MC_Utility::add_action( $tag, "{$handle}-{$tag}", [ $wrapper_obj, 'call_wrapper' ], $priority, $accepted_args );     //     288
            } else {                                                                                                                 //     289
                MC_Utility::add_filter( $tag, "{$handle}-{$tag}", [ $wrapper_obj, 'call_wrapper' ], $priority, $accepted_args );     //     290
            }                                                                                                                        //     291
        }                                                                                                                            //     292
        return TRUE;                                                                                                                 //     293
    }   # public static function wrap_hook( $handle, $wrapper, $tag, $callback, $is_action = FALSE, $priority = 10, $accepted_args = //     294
                                                                                                                                     //     295
    public static function unwrap_hook( $tag, $handle ) {                                                                            //     296
        if ( $wrapper_obj = MC_Utility::get_hook( $handle ) ) {                                                                      //     297
            if ( $wrapper_obj->is_action() ) {                                                                                       //     298
                MC_Utility::remove_action( $tag, $handle, $wrapper_obj->get_priority() );                                            //     299
                add_action( $tag, $wrapper_obj->get_callback(), $wrapper_obj->get_priority(), $wrapper_obj->get_accepted_args() );   //     300
            } else {                                                                                                                 //     301
                MC_Utility::remove_filter( $tag, $handle, $wrapper_obj->get_priority() );                                            //     302
                add_filter( $tag, $wrapper_obj->get_callback(), $wrapper_obj->get_priority(), $wrapper_obj->get_accepted_args() );   //     303
            }                                                                                                                        //     304
        }                                                                                                                            //     305
    }   # public static function unwrap_hook( $tag, $handle ) {                                                                      //     306
                                                                                                                                     //     307
}   # class MC_Hook_Wrapper {                                                                                                        //     308
                                                                                                                                     //     309
# A difficulty with WordPress actions and filters is that the parameters given to the hook often does not provide sufficient         //     310
# context. MC_Context allows (maybe indirect) callers of hooks to pass context to the hooks.                                         //     311
                                                                                                                                     //     312
#   try {                                                                                                                            //     313
#       MC_Context::push( 'some_key', 'some_value' );                                                                                //     314
#       call_some_hook_maybe_indirectly();                                                                                           //     315
#   } finally {                                                                                                                      //     316
#       MC_Context::pop();                                                                                                           //     317
#   }                                                                                                                                //     318
#                                                                                                                                    //     319
#   or take advantage of MC_Context::__destruct() for objects that live on the stack                                                 //     320
#                                                                                                                                    //     321
#   $context = new MC_Context( $key, $value );                                                                                       //     322
#                                                                                                                                    //     323
                                                                                                                                     //     324
class MC_Context {                                                                                                                   //     325
                                                                                                                                     //     326
    private static $stack = [];                                                                                                      //     327
    private static $map   = [];   # allows fast access to the stack by key                                                           //     328
    private static $heap  = [];   # global values                                                                                    //     329
                                                                                                                                     //     330
    private $index;                                                                                                                  //     331
                                                                                                                                     //     332
    public function __construct( $key, $value ) {                                                                                    //     333
        $this->index = self::push( $key, $value );                                                                                   //     334
        self::dump( 'MC_Context::__construct()' );                                                                                   //     335
    }                                                                                                                                //     336
                                                                                                                                     //     337
    public function __destruct() {                                                                                                   //     338
        self::pop_to( $this->index );                                                                                                //     339
        self::dump( 'MC_Context::__destruct()' );                                                                                    //     340
    }                                                                                                                                //     341
                                                                                                                                     //     342
    public static function push( $key, $value ) {                                                                                    //     343
        # handle multiple values with the same key                                                                                   //     344
        self::$map[ $key ][] = array_push( self::$stack, [ $key, $value ] ) - 1;                                                     //     345
        return count( self::$stack ) - 1;                                                                                            //     346
    }                                                                                                                                //     347
                                                                                                                                     //     348
    public static function pop() {                                                                                                   //     349
        array_pop( self::$map[ array_pop( self::$stack )[ 0 ] ] );                                                                   //     350
    }                                                                                                                                //     351
                                                                                                                                     //     352
    public static function pop_to( $i ) {                                                                                            //     353
        for ( ; count( self::$stack ) > $i; ) {                                                                                      //     354
            self::pop();                                                                                                             //     355
        }                                                                                                                            //     356
    }                                                                                                                                //     357
    public static function pop_to_key( $key ) {                                                                                      //     358
        self::pop_to( end( self::$map[ $key ] ) );                                                                                   //     359
    }                                                                                                                                //     360
                                                                                                                                     //     361
    public static function get( $key ) {                                                                                             //     362
        # return the topmost value on the stack with key                                                                             //     363
        if ( ! empty( self::$map[ $key ] ) ) {                                                                                       //     364
            return self::$stack[ end( self::$map[ $key ] ) ][ 1 ];                                                                   //     365
        }                                                                                                                            //     366
        # next try the heap                                                                                                          //     367
        if ( array_key_exists( $key, self::$heap ) ) {                                                                               //     368
            return self::$heap[ $key ];                                                                                              //     369
        }                                                                                                                            //     370
        return NULL;                                                                                                                 //     371
    }                                                                                                                                //     372
                                                                                                                                     //     373
    public static function set( $key, $value ) {                                                                                     //     374
        self::$heap[ $key ] = $value;                                                                                                //     375
    }                                                                                                                                //     376
                                                                                                                                     //     377
    public static function clr( $key ) {                                                                                             //     378
        unset( self::$heap[ $key ] );                                                                                                //     379
    }                                                                                                                                //     380
                                                                                                                                     //     381
    public static function dump( $where = '' ) {                                                                                     //     382
    }                                                                                                                                //     386
                                                                                                                                     //     387
}   # class MC_Context {                                                                                                             //     388
                                                                                                                                     //     389
class MC_Execution_Time {                                                                                                            //     390
                                                                                                                                     //     391
    private static $getrusage_exists          = FALSE;                                                                               //     392
    private static $max_execution_time;                                                                                              //     393
    private static $timer_interval_resolution = 0;         # max time between successive calls to near_max_execution_limit().        //     394
    private static $prev_execution_time       = 0;                                                                                   //     395
    private static $test_max_execution_time   = 0;         # set using the $wpdb->options option                                     //     396
                                                           #     'mc_xii_simple_variations_test_max_execution_time'                  //     397
                                                           # update cp_options set option_value = 3000 where option_name             //     398
                                                           #     = 'mc_xii_simple_variations_test_max_execution_time';               //     399
                                                           # delete from cp_options where option_name                                //     400
                                                           #     = 'mc_xii_simple_variations_test_max_execution_time';               //     401
    private static $time                      = 'time';                                                                              //     402
    private static $debug                     = 0x0000;    # php wp-cli.phar eval                                                    //     403
                                                           #         'update_option("mc_xii_simple_variations_debug_log", 0x0001);'  //     404
                                                                                                                                     //     405
    private static function get_rusage() {                                                                                           //     406
        if ( self::$getrusage_exists ) {                                                                                             //     408
            $usage = getrusage();                                                                                                    //     409
            return ( $usage['ru_utime.tv_sec'] * 1000000 + $usage['ru_utime.tv_usec']                                                //     410
                       + $usage['ru_stime.tv_sec'] * 1000000 + $usage['ru_stime.tv_usec'] ) - $GLOBALS['mc_xii_rusage_0'];           //     411
        }                                                                                                                            //     412
        return 0;                                                                                                                    //     413
    }                                                                                                                                //     414
                                                                                                                                     //     415
    public static function get_execution_time() {                                                                                    //     416
        $execution_time = self::get_rusage();                                                                                        //     417
        if ( ! self::$timer_interval_resolution && self::$prev_execution_time ) {                                                    //     418
            self::$timer_interval_resolution = 2 * ( $execution_time - self::$prev_execution_time );                                 //     420
            update_option( 'mc_xii_timer_interval_resolution', self::$timer_interval_resolution );                                   //     421
        } else if ( ($interval = $execution_time - self::$prev_execution_time ) > self::$timer_interval_resolution / 2 ) {           //     423
            # Adjust self::$timer_interval_resolution if the interval between successive calls to this function is greater than      //     424
            # self::$timer_interval_resolution                                                                                       //     425
            self::$timer_interval_resolution = 2 * $interval;                                                                        //     427
            update_option( 'mc_xii_timer_interval_resolution', self::$timer_interval_resolution );                                   //     428
        }                                                                                                                            //     430
        self::$prev_execution_time = $execution_time;                                                                                //     431
        return $execution_time;                                                                                                      //     432
    }                                                                                                                                //     433
                                                                                                                                     //     434
    public static function near_max_execution_limit( $share = 1 ) {                                                                  //     435
        if ( self::$getrusage_exists ) {                                                                                             //     436
            $execution_time = self::get_execution_time();                                                                            //     437
            if ( self::$timer_interval_resolution ) {                                                                                //     438
                $return = $share * self::$max_execution_time - $execution_time < self::$timer_interval_resolution;                   //     439
                if ( self::$debug | 0x0001 && $return ) {                                                                            //     440
                    error_log( '@MC_Execution_Time::near_max_execution_limit():$execution_time                  = '                  //     441
                            . $execution_time );                                                                                     //     442
                    error_log( '@MC_Execution_Time::near_max_execution_limit():self::$max_execution_time        = '                  //     443
                            . self::$max_execution_time );                                                                           //     444
                    error_log( '@MC_Execution_Time::near_max_execution_limit():self::$timer_interval_resolution = '                  //     445
                            . self::$timer_interval_resolution );                                                                    //     446
                    error_log( "@MC_Execution_Time::near_max_execution_limit():BACKTRACE = \n"                                       //     447
                            . str_replace( ', ', "\n", wp_debug_backtrace_summary() ) );                                             //     448
                }                                                                                                                    //     449
                return $return;                                                                                                      //     450
            }                                                                                                                        //     451
        }                                                                                                                            //     452
        return FALSE;                                                                                                                //     453
    }                                                                                                                                //     454
                                                                                                                                     //     455
    private static function set_max_execution_time() {                                                                               //     456
        $max_execution_time = (integer) ini_get( 'max_execution_time' ) * 1000000;                                                   //     457
        // TODO: remove this override                                                                                                //     459
        if ( $max_execution_time === 0 ) {                                                                                           //     460
            $max_execution_time = 30000000;                                                                                          //     461
        }                                                                                                                            //     462
        $max_execution_time *= 0.9;                                                                                                  //     463
        # if self::$test_max_execution_time has been set use it instead                                                              //     464
        if ( self::$test_max_execution_time > 0 && self::$test_max_execution_time < $max_execution_time ) {                          //     465
            $max_execution_time = self::$test_max_execution_time;                                                                    //     466
        }                                                                                                                            //     467
        self::$max_execution_time = $max_execution_time;                                                                             //     469
    }                                                                                                                                //     470
                                                                                                                                     //     471
    public static function set_test_max_execution_time( $max_execution_time ) {                                                      //     472
        self::$test_max_execution_time = $max_execution_time;                                                                        //     473
        self::set_max_execution_time();                                                                                              //     474
    }                                                                                                                                //     475
                                                                                                                                     //     476
    # time() returns time stamps in float seconds                                                                                    //     477
                                                                                                                                     //     478
    public static function time() {                                                                                                  //     479
        switch ( self::$time ) {                                                                                                     //     480
        case 'getrusage':                                                                                                            //     481
            return self::get_rusage() / 1000000;                                                                                     //     482
        case 'microtime':                                                                                                            //     483
            return microtime( TRUE ) / 1000000;                                                                                      //     484
        default:                                                                                                                     //     485
            return (float) time();                                                                                                   //     486
        }                                                                                                                            //     487
    }                                                                                                                                //     488
                                                                                                                                     //     489
    public static function calc_elapsed_times( $time_stamps ) {                                                                      //     490
        $elapsed_times = [];                                                                                                         //     491
        for ( $i = 1; $i < count( $time_stamps ); $i++ ) {                                                                           //     492
            $elapsed_times[] = $time_stamps[ $i ] - $time_stamps[ $i - 1 ];                                                          //     493
        }                                                                                                                            //     494
        # last item in $elapsed_times is the total of of all intervals.                                                              //     495
        $elapsed_times[] = array_sum( $elapsed_times );                                                                              //     496
        return $elapsed_times;                                                                                                       //     497
    }   # public static function calc_elapsed_times( $time_stamps ) {                                                                //     498
                                                                                                                                     //     499
    public static function sum_elapsed_times( $elapsed_times, $additional_elapsed_times ) {                                          //     500
        # last item in $elapsed_times is the number of times sum_elapsed_times() has been called.                                    //     501
        $additional_elapsed_times[] = 1;                                                                                             //     502
        if ( empty( $elapsed_times ) ) {                                                                                             //     503
            return $additional_elapsed_times;                                                                                        //     504
        }                                                                                                                            //     505
        for ( $i = 0; $i < count( $elapsed_times ); $i++ ) {                                                                         //     506
            $elapsed_times[ $i ] += $additional_elapsed_times[ $i ];                                                                 //     507
        }                                                                                                                            //     508
        return $elapsed_times;                                                                                                       //     509
    }   # public static function sum_elapsed_times( $elapsed_times, $additional_elapsed_times ) {                                    //     510
                                                                                                                                     //     511
    public static function init() {                                                                                                  //     512
        self::$debug = get_option( 'mc_xii_simple_variations_debug_log', 0x0000 );                                                   //     513
        if ( self::$getrusage_exists = function_exists( 'getrusage' ) ) {                                                            //     514
            self::$prev_execution_time       = self::get_rusage();                                                                   //     515
            self::$timer_interval_resolution = get_option( 'mc_xii_timer_interval_resolution', 0 );                                  //     517
            self::set_max_execution_time();                                                                                          //     518
        }                                                                                                                            //     519
        self::$time = self::$getrusage_exists ? 'getrusage' : ( function_exists( 'microtime' ) ? 'microtime' : 'time' );             //     520
    }                                                                                                                                //     523
                                                                                                                                     //     524
}   # class MC_Execution_Time {                                                                                                      //     525
                                                                                                                                     //     526
MC_Execution_Time::init();                                                                                                           //     527
                                                                                                                                     //     528
# MC_Array_Of_Arrays is used to store a map of product attribute values to values as a multi-dimensional array where each dimension  //     529
# corresponds to a product attribute. The value of a leaf node is the variation with the attribute values. The difficulty is the     //     530
# number of attributes is varying.                                                                                                   //     531
                                                                                                                                     //     532
class MC_Array_Of_Arrays {                                                                                                           //     533
                                                                                                                                     //     534
    private $dimensions;                                                                                                             //     535
    private $data;                                                                                                                   //     536
                                                                                                                                     //     537
    # make_array_of_arrays() returns the array of arrays in $aa. $a is an array of arrays of attribute values. E.g., for a variable  //     538
    # product with attributes color and size $a could be [ ['red', 'blue', 'green'], ['small', 'medium', 'large'] ].                 //     539
                                                                                                                                     //     540
    private static function make_array_of_arrays( &$aa, $a ) {                                                                       //     541
        $aa = [];                                                                                                                    //     542
        foreach ( current( $a ) as $k ) {                                                                                            //     543
            $aa[ $k ] = NULL;                                                                                                        //     544
            if ( next( $a ) ) {                                                                                                      //     545
                self::make_array_of_arrays( $aa[ $k ], $a );                                                                         //     546
            }                                                                                                                        //     547
            prev( $a );                                                                                                              //     548
        }                                                                                                                            //     549
    }                                                                                                                                //     550
                                                                                                                                     //     551
    # Traverse the array of arrays $aa of dimension $d and apply the function $f to the leaf nodes. $a is an array of the keys of    //     552
    # the nodes currently being traversed. $aa is passed by reference so it can be modified. If $f returns false the traversal is    //     553
    # stopped.                                                                                                                       //     554
                                                                                                                                     //     555
    private static function walk_array_of_arrays( &$aa, $d, $a, $f ) {                                                               //     556
        if ( count( $a ) < $d && is_array( $aa ) ) {                                                                                 //     557
            foreach ( $aa as $k => &$v ) {                                                                                           //     558
                array_push( $a, $k );                                                                                                //     559
                $continue = self::walk_array_of_arrays( $v, $d, $a, $f );                                                            //     560
                array_pop( $a );                                                                                                     //     561
                if ( ! $continue ) {                                                                                                 //     562
                    return FALSE;                                                                                                    //     563
                }                                                                                                                    //     564
            }                                                                                                                        //     565
        } else {                                                                                                                     //     566
            if ( ! call_user_func_array( $f, [ &$aa, $a ] ) ) {                                                                      //     567
                return FALSE;                                                                                                        //     568
            }                                                                                                                        //     569
        }                                                                                                                            //     570
        return TRUE;                                                                                                                 //     571
    }                                                                                                                                //     572
                                                                                                                                     //     573
    public function __construct( $a ) {                                                                                              //     574
        $this->dimensions = count( $a );                                                                                             //     575
        self::make_array_of_arrays( $this->data, $a );                                                                               //     576
    }                                                                                                                                //     577
                                                                                                                                     //     578
    # Traverse the array of arrays and apply the function $f to the leaf nodes.                                                      //     579
                                                                                                                                     //     580
    public function walk( $f ) {                                                                                                     //     581
        return self::walk_array_of_arrays( $this->data, $this->dimensions, [], $f );                                                 //     582
    }                                                                                                                                //     583
                                                                                                                                     //     584
    # Traverse the array of arrays using the path specified by $a and return the leaf node by reference.                             //     585
                                                                                                                                     //     586
    public function &get_item( $a ) {                                                                                                //     587
        $v =& $this->data;                                                                                                           //     588
        foreach ( $a as $k ) {                                                                                                       //     589
            $u =& $v[ $k ];                                                                                                          //     590
            unset( $v );                                                                                                             //     591
            $v =& $u;                                                                                                                //     592
            unset( $u );                                                                                                             //     593
        }                                                                                                                            //     594
        return $v;                                                                                                                   //     595
    }                                                                                                                                //     596
                                                                                                                                     //     597
    public function &get_data() {                                                                                                    //     598
        return $this->data;                                                                                                          //     599
    }                                                                                                                                //     600
                                                                                                                                     //     601
}   # class MC_Array_Of_Arrays {                                                                                                     //     602
                                                                                                                                     //     603
class MC_Map_Attributes_To_Variation_Factory {                                                                                       //     604
    private static $maps = [];                                                                                                       //     605
    public static function get( $id, $a ) {                                                                                          //     606
        if ( array_key_exists( $id, self::$maps ) ) {                                                                                //     607
            return self::$maps[ $id ];                                                                                               //     608
        }                                                                                                                            //     609
        return self::$maps[ $id ] = new MC_Array_Of_Arrays( $a );                                                                    //     610
    }                                                                                                                                //     611
}   # class MC_Map_Attributes_To_Variation_Factory {                                                                                 //     612
                                                                                                                                     //     613
class MC_Product_Simple_Variable extends WC_Product_Variable {                                                                       //     614
                                                                                                                                     //     615
    protected $extra_data = [                                                                                                        //     617
        'mc_xii_is_simple_variable' => TRUE                                                                                          //     618
    ];                                                                                                                               //     619
                                                                                                                                     //     620
    public function get_type() {                                                                                                     //     622
        // TODO: use our own type                                                                                                    //     623
        # This means that MC_Product_Simple_Variable uses the same data store as WC_Product_Variable which is currently overridden   //     624
        # from WC_Product_Variable_Data_Store_CPT to MC_Product_Variable_Data_Store_CPT                                              //     625
        return parent::get_type();                                                                                                   //     626
    }                                                                                                                                //     627
                                                                                                                                     //     628

### REDACTED lines  629 ->  663 redacted,     35 lines redacted. ###

                                                                                                                                     //     664
    public function is_purchasable() {                                                                                               //     666
        # $purchasable = parent::is_purchasable();                                                                                   //     667
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $this->get_id() ) ) {                                                 //     671
            return parent::is_purchasable();                                                                                         //     672
        }                                                                                                                            //     673
        return apply_filters( 'woocommerce_is_purchasable',                                                                          //     674
                              $this->exists() && ( 'publish' === $this->get_status()                                                 //     675
                                                   || current_user_can( 'edit_post', $this->get_id() ) )                             //     676
                                              && $this->get_min_max_price()[ 'min' ],                                                //     677
                              $this );                                                                                               //     678
    }   # public function is_purchasable() {                                                                                         //     679
                                                                                                                                     //     680

### REDACTED lines  681 ->  692 redacted,     12 lines redacted. ###

                                                                                                                                     //     693
    public static function sync( $product, $save = true ) {                                                                          //     695
        global $wpdb;                                                                                                                //     697
        if ( ! is_a( $product, 'WC_Product' ) ) {                                                                                    //     699
            $product = wc_get_product( $product );                                                                                   //     700
        }                                                                                                                            //     701
        if ( is_a( $product, 'MC_Product_Simple_Variable' ) ) {                                                                      //     702
            $managed = $wpdb->get_var( $wpdb->prepare( <<<EOD                                                                        //     703
SELECT COUNT(*) FROM $wpdb->posts p, $wpdb->postmeta m, $wpdb->postmeta n                                                            //     704
    WHERE m.post_id = p.ID AND n.post_id = p.ID                                                                                      //     705
        AND p.post_parent = %d                                                                                                       //     706
        AND m.meta_key = '_mc_xii_variation_type' AND m.meta_value = 'base'                                                          //     707
        AND n.meta_key = '_manage_stock' AND n.meta_value = 'yes'                                                                    //     708
EOD                                                                                                                                  //     709
                                                    ,  $product->get_id() ) );                                                       //     710
            if ($managed ) {                                                                                                         //     712
                # $data_store = WC_Data_Store::load( 'product-' . $product->get_type() ); - This will no work as                     //     713
                # MC_Product_Variable_Data_Store_CPT is not subclassed from MC_Product_Data_Store_CPT.                               //     714
                $data_store = WC_Data_Store::load( 'product' );                                                                      //     715
                $data_store->sync_to_no_manage_stock( $product );                                                                    //     716
            }                                                                                                                        //     717
        }                                                                                                                            //     718
        return parent::sync( $product, $save );                                                                                      //     719
    }   # public static function sync( $product, $save = true ) {                                                                    //     720
                                                                                                                                     //     721
    public function get_mc_xii_is_simple_variable( $context = 'view' ) {                                                             //     723
        return $this->get_prop( 'mc_xii_is_simple_variable', $context );                                                             //     724
    }                                                                                                                                //     725
                                                                                                                                     //     726
    public function get_min_max_price() {                                                                                            //     727
        $data_store = $this->get_data_store();                                                                                       //     730
        $prices     = $data_store->get_min_max_prices( $this, [ 'in_stock' ] );                                                      //     731
        $min        = 0;                                                                                                             //     732
        $max        = 0;                                                                                                             //     733
        foreach ( $prices as $attribute => $price ) {                                                                                //     734
            $optional_attribute = MC_Simple_Variation_Functions::is_optional_attribute_obsolete( $attribute );                       //     735
            if ( ! $optional_attribute && ( $price[ 'min' ] === '' || $price[ 'max' ] === '' ) ) {                                   //     736
                $min = $max = '';                                                                                                    //     737
                break;                                                                                                               //     738
            }                                                                                                                        //     739
            $min += $optional_attribute ? 0 : $price[ 'min' ];                                                                       //     740
            $max += $price[ 'max' ];                                                                                                 //     741
        }                                                                                                                            //     742
        return [ 'min' => $min, 'max' => $max ];                                                                                     //     743
    }   # public function get_min_max_price() {                                                                                      //     744
                                                                                                                                     //     745
    public function get_number_of_variations() {                                                                                     //     746
        $data_store = $this->get_data_store();                                                                                       //     748
        return $data_store->get_number_of_variations( $this );                                                                       //     749
    }   # public function get_number_of_variations() {                                                                               //     750
                                                                                                                                     //     751
    public function get_all_children( $visible_only = '' ) {                                                                         //     753
        $data_store = $this->get_data_store();                                                                                       //     755
        return $data_store->read_all_children( $this, TRUE )[ 'all' ];                                                               //     756
    }   #   public function get_all_children( $visible_only = '' ) {                                                                 //     757
}   # class MC_Product_Simple_Variable extends WC_Product_Variable {                                                                 //     758
                                                                                                                                     //     759

### REDACTED lines  760 ->  762 redacted,      3 lines redacted. ###

                                                                                                                                     //     763
class MC_Simple_Variation_Functions {                                                                                                //     764
                                                                                                                                     //     765

### REDACTED lines  766 -> 1076 redacted,    311 lines redacted. ###

                                                                                                                                     //    1077
    # init() must run before actions 'init' and 'wp_loaded' are done as init() adds these actions.                                   //    1078
    public static function init() {                                                                                                  //    1079
                                                                                                                                     //    1080

### REDACTED lines 1081 -> 1324 redacted,    244 lines redacted. ###

                                                                                                                                     //    1325
        MC_Utility::add_filter( 'woocommerce_get_children', 'mc_xii_woocommerce_get_children',                                       //    1326
                function( $children, $product, $visible ) {                                                                          //    1327
            if ( $visible ) {                                                                                                        //    1329
                # Remove the base variations from $children.                                                                         //    1330
                $data_store = new MC_Product_Variable_Data_Store_CPT();                                                              //    1331
                $base_ids   = $data_store->get_base_variation_ids( $product );                                                       //    1332
                $children   = array_diff( $children, $base_ids );                                                                    //    1333
            }                                                                                                                        //    1334
            return $children;                                                                                                        //    1335
        }, 10, 3 );   # MC_Utility::add_filter( 'woocommerce_get_children', 'mc_xii_woocommerce_get_children',                       //    1336
                                                                                                                                     //    1337

### REDACTED lines 1338 -> 1647 redacted,    310 lines redacted. ###

                                                                                                                                     //    1648
        MC_Utility::add_action( 'woocommerce_email', 'mc_xii_woocommerce_email', function( $emails ) {                               //    1649
            remove_action( 'woocommerce_low_stock_notification',            [ $emails, 'low_stock' ] );                              //    1650
            remove_action( 'woocommerce_no_stock_notification',             [ $emails, 'no_stock'  ] );                              //    1651
            remove_action( 'woocommerce_product_on_backorder_notification', [ $emails, 'backorder' ] );                              //    1652
            MC_Utility::add_action( 'woocommerce_low_stock_notification', 'mc_xii_woocommerce_low_stock_notification',               //    1653
                    function( $product ) use ( $emails ) {                                                                           //    1654
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1655
                    $emails->low_stock( $product );                                                                                  //    1656
                }                                                                                                                    //    1657
                $amount = get_option( 'woocommerce_notify_low_stock_amount' );                                                       //    1658
                foreach ( self::get_base_variations_of_compound_variations( $product->get_id() ) as $base_id ) {                     //    1659
                    $base_variation = wc_get_product( $base_id );                                                                    //    1660
                    if ( $base_variation->get_stock_quantity() <= $amount ) {                                                        //    1661
                        $emails->low_stock( $base_variation );                                                                       //    1662
                    }                                                                                                                //    1663
                }                                                                                                                    //    1664
            }, 10, 1 );                                                                                                              //    1665
            MC_Utility::add_action( 'woocommerce_no_stock_notification', 'mc_xii_woocommerce_no_stock_notification',                 //    1666
                    function( $product ) use ( $emails ) {                                                                           //    1667
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1668
                    $emails->no_stock( $product );                                                                                   //    1669
                }                                                                                                                    //    1670
                $amount = get_option( 'woocommerce_notify_no_stock_amount' );                                                        //    1671
                foreach ( self::get_base_variations_of_compound_variations( $product->get_id() ) as $base_id ) {                     //    1672
                    $base_variation = wc_get_product( $base_id );                                                                    //    1673
                    if ( $base_variation->get_stock_quantity() <= $amount ) {                                                        //    1674
                        $emails->no_stock( $base_variation );                                                                        //    1675
                    }                                                                                                                //    1676
                }                                                                                                                    //    1677
            }, 10, 1 );                                                                                                              //    1678
                                                                                                                                     //    1679
            MC_Utility::add_action( 'woocommerce_product_on_backorder_notification',                                                 //    1680
                    'mc_xii_woocommerce_product_on_backorder_notification', function( $args ) use ( $emails ) {                      //    1681
                $product = $args['product'];                                                                                         //    1682
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1683
                    $emails->backorder( $args );                                                                                     //    1684
                }                                                                                                                    //    1685
                foreach ( self::get_base_variations_of_compound_variations( $product->get_id() ) as $base_id ) {                     //    1686
                    $base_variation = wc_get_product( $base_id );                                                                    //    1687
                    if ( $base_variation->get_stock_quantity() < 0 ) {                                                               //    1688
                        $args['product'] = $base_variation;                                                                          //    1689
                        $emails->backorder( $args );                                                                                 //    1690
                    }                                                                                                                //    1691
                }                                                                                                                    //    1692
            }, 10, 1 );                                                                                                              //    1693
                                                                                                                                     //    1694
            MC_Utility::add_filter( 'woocommerce_email_content_low_stock', 'mc_xii_woocommerce_email_content_low_stock',             //    1695
                    function( $message, $product ) {                                                                                 //    1696
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1697
                    return $message;                                                                                                 //    1698
                }                                                                                                                    //    1699
                $regex = '!' . sprintf( __( '%1$s is low in stock. There are %2$s left.', 'woocommerce' ), '(.+?)', '(\d+)' ) . '!'; //    1700
                if ( preg_match( $regex, $message, $matches ) === 1 ) {                                                              //    1701
                    if ( ! is_numeric( $matches[1] ) ) {                                                                             //    1702
                        $full_name = $matches[1];                                                                                    //    1703
                        $stock     = $matches[2];                                                                                    //    1704
                    } else {                                                                                                         //    1705
                        $full_name = $matches[2];                                                                                    //    1706
                        $stock     = $matches[1];                                                                                    //    1707
                    }                                                                                                                //    1708
                    if ( ( $end = strpos( $full_name, ')' ) ) !== FALSE ) {                                                          //    1709
                        $name = substr( $full_name, 0, $end + 1 );                                                                   //    1710
                    } else {                                                                                                         //    1711
                        $name = sprintf( '%2$s (%1$s)', '#' . $product->get_id(), $product->get_name() );                            //    1712
                    }                                                                                                                //    1713
                    $message = sprintf( __( '%1$s is low in stock. There are %2$s left.', 'woocommerce' ), $name, $stock );          //    1714
                }                                                                                                                    //    1715
                return $message;                                                                                                     //    1716
            }, 10, 2 );                                                                                                              //    1717
                                                                                                                                     //    1718
            MC_Utility::add_filter( 'woocommerce_email_content_no_stock', 'mc_xii_woocommerce_email_content_no_stock',               //    1719
                    function( $message, $product ) {                                                                                 //    1720
                if ( ! self::is_variation_of_simple_variable( $product ) ) {                                                         //    1721
                    return $message;                                                                                                 //    1722
                }                                                                                                                    //    1723
                $regex = '!' . sprintf( __( '%s is out of stock.', 'woocommerce' ), '(.+?)' ) . '!';                                 //    1724
                if ( preg_match( $regex, $message, $matches ) === 1 ) {                                                              //    1725
                    if ( ( $end = strpos( $matches[1], ')' ) ) !== FALSE ) {                                                         //    1726
                        $name = substr( $matches[1], 0, $end + 1 );                                                                  //    1727
                    }                                                                                                                //    1728
                }                                                                                                                    //    1729
                if ( empty( $name ) ) {                                                                                              //    1730
                    $name = sprintf( '%2$s (%1$s)', '#' . $product->get_id(), $product->get_name() );                                //    1731
                }                                                                                                                    //    1732
                $message = sprintf( __( '%s is out of stock.', 'woocommerce' ), $name );                                             //    1733
                return $message;                                                                                                     //    1734
            }, 10, 2 );                                                                                                              //    1735
                                                                                                                                     //    1736
            MC_Utility::add_filter( 'woocommerce_email_content_backorder', 'mc_xii_woocommerce_email_content_backorder',             //    1737
                    function( $message, $args ) {                                                                                    //    1738
                if ( ! self::is_variation_of_simple_variable( $args['product'] ) ) {                                                 //    1739
                    return $message;                                                                                                 //    1740
                }                                                                                                                    //    1741
                $regex = '!' . sprintf( __( '%1$s units of %2$s have been backordered in order #%3$s.', 'woocommerce' ),             //    1742
                                            '(\d+)', '(.+?)', '(\d+)' ) . '!';                                                       //    1743
                if ( preg_match( $regex, $message, $matches ) === 1 ) {                                                              //    1744
                    for ( $i = 1; $i < count( $matches ); $i++ ) {                                                                   //    1745
                        if ( $matches[i] != $args['quantity'] && $matches[i] != $args['order_id'] ) {                                //    1746
                            if ( ( $end = strpos( $matches[i], ')' ) ) !== FALSE ) {                                                 //    1747
                                $name = substr( $matches[i], 0, $end + 1 );                                                          //    1748
                            }                                                                                                        //    1749
                            break;                                                                                                   //    1750
                        }                                                                                                            //    1751
                    }                                                                                                                //    1752
                }                                                                                                                    //    1753
                if ( empty( $name ) ) {                                                                                              //    1754
                    $name = sprintf( '%2$s (%1$s)', '#' . $args['product']->get_id(), $args['product']->get_name() );                //    1755
                }                                                                                                                    //    1756
                $message = sprintf( __( '%1$s units of %2$s have been backordered in order #%3$s.', 'woocommerce' ),                 //    1757
                                        $args['quantity'], $name, $args['order_id'] );                                               //    1758
                return $message;                                                                                                     //    1759
            }, 10, 2 );                                                                                                              //    1760
        }, 10, 1 );   # MC_Utility::add_action( 'woocommerce_email', 'mc_xii_woocommerce_email', function( $emails ) {               //    1761
                                                                                                                                     //    1762

### REDACTED lines 1763 -> 1783 redacted,     21 lines redacted. ###

                                                                                                                                     //    1784
        MC_Utility::add_action( 'woocommerce_scheduled_sales', 'mc_xii_woocommerce_scheduled_sales', function() {                    //    1785
            # Classic Commerce's wc_scheduled_sales() runs at priority 10 and does not handle simple variations because              //    1787
            # MC_Product_Data_Store_CPT::get_starting_sales() and MC_Product_Data_Store_CPT::get_ending_sales() omits them.          //    1788
            # Do schedule sales for simple variations.                                                                               //    1791
            $data_store = WC_Data_Store::load( 'product' );                                                                          //    1792
            $product_ids = array_merge( $data_store->sv_get_starting_sales(), $data_store->sv_get_ending_sales() );                  //    1793
            self::do_after_product_sales_update( $product_ids );                                                                     //    1794
        }, 11 );   # MC_Utility::add_action( 'woocommerce_scheduled_sales', 'mc_xii_woocommerce_scheduled_sales', function() {       //    1795
                                                                                                                                     //    1796

### REDACTED lines 1797 -> 1854 redacted,     58 lines redacted. ###

                                                                                                                                     //    1855
        add_filter( 'woocommerce_order_item_get_formatted_meta_data',                                                                //    1856
                    'MC_Simple_Variation_Functions::woocommerce_order_item_get_formatted_meta_data', 10, 2 );                        //    1857
                                                                                                                                     //    1858

### REDACTED lines 1859 -> 1895 redacted,     37 lines redacted. ###

                                                                                                                                     //    1896
        if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {                                            //    1897
                                                                                                                                     //    1898
            # Start of actions and filters used by the frontend including AJAX calls.                                                //    1899
                                                                                                                                     //    1900

### REDACTED lines 1901 -> 2202 redacted,    302 lines redacted. ###

                                                                                                                                     //    2203
            MC_Utility::add_action( 'woocommerce_product_additional_information',                                                    //    2204
                                    'mc_xii_woocommerce_product_additional_information_ob_start', function() {                       //    2205
                # Called by single product select options page.                                                                      //    2206
                # We need to process the ouput of wc_display_product_attributes() which runs at priority 10 so turn on output        //    2207
                # buffering at priority 9 and end output buffering and process buffer at priority 11.                                //    2208
                global $product;                                                                                                     //    2209
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) ) {                                                  //    2210
                    return;                                                                                                          //    2211
                }                                                                                                                    //    2212
                ob_start( function( $buffer ) {                                                                                      //    2213
                    $buffer = str_replace( self::UNSELECTED . ', ', '', $buffer );                                                   //    2214
                    $buffer = self::remove_optional_suffix_in_string( $buffer );                                                     //    2215
                    return $buffer;                                                                                                  //    2216
                } );                                                                                                                 //    2217
            }, 9 );                                                                                                                  //    2218
                                                                                                                                     //    2219
            MC_Utility::add_action( 'woocommerce_product_additional_information',                                                    //    2220
                                    'mc_xii_woocommerce_product_additional_information_ob_end', function() {                         //    2221
                global $product;                                                                                                     //    2222
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) ) {                                                  //    2223
                    return;                                                                                                          //    2224
                }                                                                                                                    //    2225
                ob_end_flush();                                                                                                      //    2226
            }, 11 );                                                                                                                 //    2227
                                                                                                                                     //    2228
            # $doing_dropdown_variation_attributes is true when executing code on the product add to cart page                       //    2229
            # - classic-commerce\templates\single-product\add-to-cart\variable.php                                                   //    2230
                                                                                                                                     //    2231
            $doing_dropdown_variation_attributes = FALSE;                                                                            //    2232
            MC_Utility::add_action( 'woocommerce_before_variations_form', 'mc_xii_woocommerce_before_variations_form',               //    2233
                    function() use ( &$doing_dropdown_variation_attributes ) {                                                       //    2234
                global $product;                                                                                                     //    2235
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) ) {                                                  //    2238
                    return;                                                                                                          //    2239
                }                                                                                                                    //    2240
                $doing_dropdown_variation_attributes = TRUE;                                                                         //    2241
                # We will need to process the variations form output so ...                                                          //    2242
                ob_start( function( $buffer ) {                                                                                      //    2243
                    # remove optional suffix                                                                                         //    2244
                    $buffer = preg_replace( '#(<label\s.*?>.*?)' . self::OPTIONAL . '(.*?</label>)#', '$1$2', $buffer );             //    2245
                    $buffer = preg_replace( '#(<option\s.*?>.*?)' . self::OPTIONAL . '(.*?</option>)#', '$1$2', $buffer );           //    2246
                    $buffer = preg_replace_callback( '#<select\s.*?>#', function( $matches ) {                                       //    2247
                        return str_replace( '>', ' autocomplete="off" style="width: 70%;">', $matches[0] );                          //    2248
                    }, $buffer );                                                                                                    //    2249
                    return $buffer;                                                                                                  //    2250
                } );                                                                                                                 //    2251
            } );                                                                                                                     //    2252
                                                                                                                                     //    2253
            MC_Utility::add_action( 'woocommerce_after_variations_form', 'mc_xii_woocommerce_after_variations_form',                 //    2254
                    function() use ( &$doing_dropdown_variation_attributes ) {                                                       //    2255
                global $product;                                                                                                     //    2256
                if ( ! self::is_simple_variable( absint( $product->get_id() ) ) || ! $doing_dropdown_variation_attributes ) {        //    2259
                    return;                                                                                                          //    2260
                }                                                                                                                    //    2261
                ob_end_flush();                                                                                                      //    2262
                $doing_dropdown_variation_attributes = FALSE;                                                                        //    2263
            } );                                                                                                                     //    2264
                                                                                                                                     //    2265

### REDACTED lines 2266 -> 2448 redacted,    183 lines redacted. ###

                                                                                                                                     //    2449
            add_filter( 'woocommerce_add_cart_item_data',                                                                            //    2450
                        'MC_Simple_Variation_Functions::woocommerce_add_cart_item_data', 10, 4 );                                    //    2451
                                                                                                                                     //    2452
            add_filter( 'woocommerce_order_again_cart_item_data',                                                                    //    2453
                        'MC_Simple_Variation_Functions::woocommerce_order_again_cart_item_data', 10, 3 );                            //    2454
                                                                                                                                     //    2455

### REDACTED lines 2456 -> 2489 redacted,     34 lines redacted. ###

                                                                                                                                     //    2490
            # End of actions and filters used by the frontend including AJAX calls.                                                  //    2491
                                                                                                                                     //    2492
        }   # if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {                                      //    2493
                                                                                                                                     //    2494
    }   # public static function init() {                                                                                            //    2495
                                                                                                                                     //    2496
    public static function admin_init() {                                                                                            //    2497
                                                                                                                                     //    2498

### REDACTED lines 2499 -> 3252 redacted,    754 lines redacted. ###

                                                                                                                                     //    3253
        if ( $pagenow === 'post.php'  && array_key_exists( 'post', $_GET ) && ! empty( $post = $_GET[ 'post' ] )                     //    3254
                && self::is_simple_variable( $post ) ) {                                                                             //    3255
            MC_Utility::add_action( 'admin_init', 'mc_xii_admin_init', function( $id ) {                                             //    3256
                ob_start( function( $buffer ) {                                                                                      //    3257
                    $buffer = preg_replace( '#(<a\s+class="submitdelete deletion").*>(.+)</a>#',                                     //    3258
                                            '$1 href="' . admin_url( 'admin.php?page=wc-settings&tab=simple_variations' )            //    3259
                                                . '">$2 - please use Settings -> Simple Variations -> Optional Features</a>',        //    3260
                                            $buffer );                                                                               //    3261
                    return $buffer;                                                                                                  //    3262
                } );                                                                                                                 //    3263
            }, 11 );   # Since this code executes as a priority 10 'admin_init' action to install this hook priority 11 is needed.   //    3264
        }   # if ( $pagenow === 'post.php' ) {                                                                                       //    3265
                                                                                                                                     //    3266
        if ( $pagenow === 'post.php' && array_key_exists( 'post', $_GET ) && ! empty( $post = $_GET[ 'post' ] )                      //    3267
                && self::is_simple_variable( $post ) ) {                                                                             //    3268
            # Change 'Attributes' and 'Variations' to 'Component Types' and 'Components'.                                            //    3269
            MC_Utility::add_filter( 'woocommerce_product_data_tabs', 'mc_xii_woocommerce_product_data_tabs', function( $tabs ) {     //    3270
                $tabs[ 'attribute'  ][ 'label' ] = 'Component Types';                                                                //    3272
                $tabs[ 'variations' ][ 'label' ] = 'Components';                                                                     //    3273
                return $tabs;                                                                                                        //    3274
            } );                                                                                                                     //    3275
        }   # if ( $pagenow === 'post.php' && array_key_exists( 'post', $_GET ) && ! empty( $post = $_GET[ 'post' ] )                //    3276
                                                                                                                                     //    3277
    }   # public static function admin_init() {                                                                                      //    3278
                                                                                                                                     //    3279

### REDACTED lines 3280 -> 3401 redacted,    122 lines redacted. ###

                                                                                                                                     //    3402
    public static function is_optional_attribute( $attribute, $product_id = -1, $product = NULL ) {                                  //    3403
        if ( $product instanceof WC_Product ) {                                                                                      //    3405
            $attribute_objects = $product->get_attributes();                                                                         //    3406
            $canonicalized_attribute = substr_compare( $attribute, 'attribute_', 0, 10 ) === 0 ? substr( $attribute, 10 )            //    3409
                                                                                               : $attribute;                         //    3410
            if ( ! empty( $attribute_objects[ $canonicalized_attribute ] ) ) {                                                       //    3411
                $attribute_object = $attribute_objects[ $canonicalized_attribute ];                                                  //    3412
                if ( $attribute_object instanceof MC_Product_Attribute ) {                                                           //    3413
                    return $attribute_object->get_optional();                                                                        //    3416
                }                                                                                                                    //    3417
            }                                                                                                                        //    3418
        }                                                                                                                            //    3419
        return self::is_optional_attribute_obsolete( $attribute );                                                                   //    3421
    }   # public static function is_optional_attribute( $attribute, $product_id = -1, $product = NULL ) {                            //    3422
                                                                                                                                     //    3423
    public static function is_optional_attribute_obsolete( $attribute ) {                                                            //    3424
        return strlen( $attribute ) > self::$optional_suffix_length                                                                  //    3425
            && ! substr_compare( $attribute, self::OPTIONAL, - self::$optional_suffix_length );                                      //    3426
    }                                                                                                                                //    3427
                                                                                                                                     //    3428
    public static function remove_optional_suffix( $attribute_name ) {                                                               //    3429
        return substr( $attribute_name, 0, - self::$optional_suffix_length );                                                        //    3430
    }                                                                                                                                //    3431
                                                                                                                                     //    3432
    public static function remove_optional_suffix_in_string( $string ) {                                                             //    3433
        return str_replace( self::OPTIONAL, '', $string );                                                                           //    3434
    }                                                                                                                                //    3435
                                                                                                                                     //    3436
    # N.B. 'is_optional' is not a key in the database post_meta field '_product_attributes' rather in the database the optional      //    3437
    #      attributes have a self::OPTIONAL suffix.                                                                                  //    3438
    # php wp-cli.phar eval 'print_r(get_post_meta(45,"_product_attributes"));'                                                       //    3439
    public static function prepare_request_product_attributes( &$data, $product_id ) {                                               //    3440
        # prepare attributes for call to WC_Meta_Box_Product_Data::prepare_attributes()                                              //    3441
        # insert unselected attribute value and insert missing visibility and variation attributes                                   //    3442
        if ( ! empty( $data['attribute_names'] ) ) {                                                                                 //    3443
            foreach ( $data['attribute_names'] as $index => &$name ) {                                                               //    3444
                if ( ! $name ) {                                                                                                     //    3445
                    continue;                                                                                                        //    3446
                }                                                                                                                    //    3447
                if ( ! empty( $data['attribute_optional'][ $index ] ) ) {                                                            //    3448
                    # optional component so add optional suffix to attribute slug                                                    //    3449
                    if ( ! self::is_optional_attribute_obsolete( $name ) ) {                                                         //    3450
                        $name .= self::OPTIONAL;                                                                                     //    3451
                    }                                                                                                                //    3452
                } else {                                                                                                             //    3453
                    # not optional so remove optional suffix from attribute slug if it exists                                        //    3454
                    if ( self::is_optional_attribute_obsolete( $name ) ) {                                                           //    3455
                        $name = substr( $name, 0, - $suffix_len );                                                                   //    3456
                    }                                                                                                                //    3457
                }                                                                                                                    //    3458
            }                                                                                                                        //    3459
        }                                                                                                                            //    3460
        # remove my for_simple_variation attribute                                                                                   //    3461
        unset( $data['attribute_for_simple_variation'], $data['attribute_optional'] );                                               //    3462
    }   # private static function prepare_request_product_attributes( &$data ) {                                                     //    3463
                                                                                                                                     //    3464

### REDACTED lines 3465 -> 4081 redacted,    617 lines redacted. ###

                                                                                                                                     //    4082
    private static function do_after_product_sales_update( $product_ids ) {                                                          //    4083
        error_log( 'do_after_product_sales_update():current_filter() = ' . current_filter() );                                       //    4084
        $time_stamp = current_time( 'timestamp', TRUE );                                                                             //    4085
        $base_ids   = [];                                                                                                            //    4086
        foreach ( $product_ids as $id ) {                                                                                            //    4087
            if ( self::is_base_variation( $id ) ) {                                                                                  //    4088
                update_post_meta( $id, '_mc_xii_sales_data_synced_at', $time_stamp );                                                //    4089
                $base_ids[] = $id;                                                                                                   //    4090
            }                                                                                                                        //    4091
        }                                                                                                                            //    4092
        $stale_compound_variations_ids = self::get_stale_compound_variations_of_base_variations_wrt_sales( $base_ids, $time_stamp ); //    4093
        $not_completed = FALSE;                                                                                                      //    4094
        foreach( $stale_compound_variations_ids as $id ) {                                                                           //    4095
            self::calculate_sale_data_of_compound_variations_from_their_base_variations( $id, NULL, TRUE );                          //    4096
            # Since this PHP execution session is shared do not use more than half of the available execution time.                  //    4097
            if ( MC_Execution_Time::near_max_execution_limit( 0.5 ) ) {                                                              //    4098
                $not_completed = TRUE;                                                                                               //    4099
                break;                                                                                                               //    4100
            }                                                                                                                        //    4101
        }                                                                                                                            //    4102
        if ( $not_completed ) {                                                                                                      //    4103
            wp_schedule_single_event( time(), 'woocommerce_scheduled_sales', [ 'unique' => time() ] );                               //    4104
            $url  = site_url( 'wp-cron.php' ) . '?' . $_SERVER['QUERY_STRING'];                                                      //    4105
            $args = [                                                                                                                //    4106
                'timeout'   => 0.01,                                                                                                 //    4107
                'blocking'  => false,                                                                                                //    4108
                'sslverify' => apply_filters( 'https_local_ssl_verify', false )                                                      //    4109
            ];                                                                                                                       //    4110
            wp_remote_post( $url, $args );                                                                                           //    4111
        } else {                                                                                                                     //    4112
            # Since we are all done it is ok to clear the relevant _sale_price_dates_(from|to) of the relevant base variations.      //    4113
            # However, wc_scheduled_sales() should have already handle this.                                                         //    4114
        }                                                                                                                            //    4115
    }   # private static function do_after_product_sales_update( $product_ids ) {                                                    //    4116
                                                                                                                                     //    4117
    private static function get_stale_compound_variations_of_base_variations_wrt_sales( $base_ids, $time_stamp ) {                   //    4118
        $type_compound = self::TYPE_COMPOUND;                                                                                        //    4119
        $compound_ids = [];                                                                                                          //    4120
        foreach ( $base_ids as $base_id ) {                                                                                          //    4121
            # Get stale compound variations that use these base variations.                                                          //    4122
            $ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                            //    4123
SELECT m.post_id FROM $wpdb->postmeta m                                                                                              //    4124
    JOIN $wpdb->postmeta as postmeta_type    ON m.post_id = postmeta_type.post_id                                                    //    4125
    JOIN $wpdb->postmeta as postmeta_sync_at ON m.post_id = postmeta_sync_at.post_id                                                 //    4126
WHERE postmeta_type.meta_key = '_mc_xii_variation_type' AND postmeta_type.meta_value = '$type_compound'                              //    4127
    AND postmeta_sync_at.meta_key = '_mc_xii_sales_data_synced_at' AND postmeta_sync_at.meta_value < %d                              //    4128
    AND m.meta_key = '_mc_xii_base_variations' AND m.meta_value LIKE '%%<%d>%%'                                                      //    4129
EOD                                                                                                                                  //    4130
                                                   , $base_id, $time_stamp ) );                                                      //    4131
            $compound_ids += $ids;                                                                                                   //    4132
        }                                                                                                                            //    4133
        return array_unique( $compound_ids );                                                                                        //    4134
    }   # private static function get_stale_compound_variations_of_base_variations( $base_ids, $time_stamp ) {                       //    4135
                                                                                                                                     //    4136
    # Update the sales data of compound variation of a simple variable product from the sales data of its base variations.           //    4137
    # N.B. After applying the sale price wc_scheduled_sales() changes the meta_value of '_sale_price_dates_from' to ''.              //    4138
    # N.B. calculate_sale_data_of_compound_variations_from_their_base_variations() does not use the meta_value '_price' of base      //    4139
    #      variations, i.e., it does not matter if the base variations have been updated with the current sale price so it can be    //    4140
    #      called before or after wc_scheduled_sales().                                                                              //    4141
    # N.B. calculate_sale_data_of_compound_variations_from_their_base_variations() does not modify the base variations in anyway.    //    4142
    # N.B. The sales data of virtual compound variations is handled by MC_Product_Variation_Data_Store_CPT::read().                  //    4143
    # Since, it is inexpensive to do also update stock. This may be useful if we choose to run                                       //    4144
    # calculate_sale_data_of_compound_variations_from_their_base_variations() before a product is displayed in the frontend.         //    4145
    # Then the correct stock can be displayed even if sync_compound_variations_with_base_variations() has not completed.             //    4146
    private static function calculate_sale_data_of_compound_variations_from_their_base_variations( $product, $bases = NULL,          //    4147
                                                                                                   $update_database = TRUE ) {       //    4148
        # error_log( 'calculate_sale_data_of_compound_variations_from_their_base_variations():current_filter() = '                   //    4149
        #            . current_filter() );                                                                                           //    4150
        # error_log( 'calculate_sale_data_of_compound_variations_from_their_base_variations():BACKTRACE = '                          //    4151
        #            . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true) );                                             //    4152
        MC_Filter_Recorder::record( 'calculate_sale_data_of_compound_variations_from_their_base_variations' );                       //    4153
        global $wpdb;                                                                                                                //    4154
        if (       doing_action( 'woocommerce_payment_complete' )        || doing_action( 'woocommerce_order_status_completed' )     //    4155
                || doing_action( 'woocommerce_order_status_processing' ) || doing_action( 'woocommerce_order_status_on-hold' )       //    4156
                || doing_action( 'woocommerce_order_status_refunded' )   || doing_action( 'woocommerce_order_status_cancelled' ) ) { //    4157
            # The product is being updated and is not stable and continuing would give incorrect results.                            //    4158
            return;                                                                                                                  //    4159
        }                                                                                                                            //    4160
        # The sales to/from date of compound variations must be dynamically computed from the sales to/from date of its base         //    4161
        # variations.                                                                                                                //    4162
        $meta_keys          = [ '_regular_price', '_sale_price', '_sale_price_dates_from', '_sale_price_dates_to', '_stock' ];       //    4163
        $meta_keys_count    = count( $meta_keys );                                                                                   //    4164
        $meta_keys_imploded = '"' . implode( '", "', $meta_keys ) . '"';                                                             //    4165
        if ( ! $bases ) {                                                                                                            //    4166
            $base_ids = self::get_base_variations( $product );                                                                       //    4167
            if ( ! $base_ids || count( $base_ids ) <= 1 ) {                                                                          //    4168
                return;                                                                                                              //    4169
            }                                                                                                                        //    4170
            $base_ids_count = count( $base_ids );                                                                                    //    4171
            $base_ids       = implode( ', ', $base_ids );                                                                            //    4172
            $results        = $wpdb->get_results( <<<EOD                                                                             //    4173
SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE post_id IN ( $base_ids ) AND meta_key IN ( $meta_keys_imploded )     //    4174
EOD                                                                                                                                  //    4175
            );                                                                                                                       //    4176
            $bases = [];                                                                                                             //    4177
            foreach ( $results as $result ) {                                                                                        //    4178
                $post_id = $result->post_id;                                                                                         //    4179
                if ( ! array_key_exists( $post_id, $bases ) ) {                                                                      //    4180
                    $bases[ $post_id ] = [];                                                                                         //    4181
                }                                                                                                                    //    4182
                $bases[ $post_id ][ substr( $result->meta_key, 1 ) ] = $result->meta_value;                                          //    4183
            }                                                                                                                        //    4184
            if ( count( $bases ) != $base_ids_count ) {                                                                              //    4185
                error_log( 'count( $bases ) != $base_ids_count' );                                                                   //    4186
                error_log( '$bases = ' . print_r( $bases, true ) );                                                                  //    4187
                return;                                                                                                              //    4188
            }                                                                                                                        //    4189
        }                                                                                                                            //    4190
        foreach ( $bases as $post_id => &$base ) {                                                                                   //    4191
            $missing = count( $base ) < $meta_keys_count;                                                                            //    4192
            $base = (object) $base;                                                                                                  //    4193
            if ( $missing ) {                                                                                                        //    4194
                foreach ( $meta_keys as $meta_key ) {                                                                                //    4195
                    if ( ! property_exists( $base, substr( $meta_key, 1 ) ) ) {                                                      //    4196
                    }                                                                                                                //    4200
                }                                                                                                                    //    4201
            }                                                                                                                        //    4202
        }                                                                                                                            //    4203
        unset( $base );                                                                                                              //    4204
        $now                 = (new DateTime())->getTimestamp();                                                                     //    4205
        $update_database     = TRUE;                                                                                                 //    4206
        $regular_price       = 0;                                                                                                    //    4207
        $sale_price          = 0;                                                                                                    //    4208
        $from_sale_date      = NULL;                                                                                                 //    4209
        $to_sale_date        = NULL;                                                                                                 //    4210
        $next_from_sale_date = NULL;                                                                                                 //    4211
        $stock               = PHP_INT_MAX;                                                                                          //    4212
        foreach ( $bases as $base_id => $base ) {                                                                                    //    4213
            if ( is_numeric( $stock ) ) {                                                                                            //    4214
                if ( property_exists( $base, 'stock' ) && is_numeric( $base->stock ) ) {                                             //    4215
                    if ( $base->stock < $stock ) {                                                                                   //    4216
                        $stock = $base->stock;                                                                                       //    4217
                    }                                                                                                                //    4218
                } else {                                                                                                             //    4219
                    $stock = '';                                                                                                     //    4220
                }                                                                                                                    //    4221
            }                                                                                                                        //    4222
            $sale_price_dates_from = $base->sale_price_dates_from;                                                                   //    4223
            $sale_price_dates_to   = $base->sale_price_dates_to;                                                                     //    4224
            if ( ! $sale_price_dates_from ) {                                                                                        //    4225
                $sale_price_dates_from = 0;                                                                                          //    4226
            }                                                                                                                        //    4227
            if ( ! $sale_price_dates_to ) {                                                                                          //    4228
                $sale_price_dates_to = PHP_INT_MAX;                                                                                  //    4229
            }                                                                                                                        //    4230
            if ( ! is_numeric( $base->sale_price ) || $sale_price_dates_to < $now ) {                                                //    4231
                if ( is_numeric( $base->regular_price ) ) {                                                                          //    4232
                    if ( is_numeric( $sale_price ) ) {                                                                               //    4233
                        $sale_price    += $base->regular_price;                                                                      //    4234
                    }                                                                                                                //    4235
                    if ( is_numeric ( $regular_price ) ) {                                                                           //    4236
                        $regular_price += $base->regular_price;                                                                      //    4237
                    }                                                                                                                //    4238
                } else {                                                                                                             //    4239
                    $sale_price     = '';                                                                                            //    4240
                    $regular_price  = '';                                                                                            //    4241
                }                                                                                                                    //    4242
                continue;                                                                                                            //    4243
            }                                                                                                                        //    4244
            if ( $sale_price_dates_from < $now ) {                                                                                   //    4245
                $sale_price_dates_from = 0;                                                                                          //    4246
            }                                                                                                                        //    4247
            if ( is_numeric( $base->sale_price ) && ( $from_sale_date === NULL || $sale_price_dates_from < $from_sale_date ) ) {     //    4248
                $from_sale_date = $sale_price_dates_from;                                                                            //    4249
                $to_sale_date   = $sale_price_dates_to;                                                                              //    4250
                $sale_price     = $regular_price + $base->sale_price;                                                                //    4251
            } else if ( is_numeric( $base->sale_price ) && $sale_price_dates_from === $from_sale_date ) {                            //    4252
                if ( is_numeric( $sale_price ) ) {                                                                                   //    4253
                    $sale_price += $base->sale_price;                                                                                //    4254
                }                                                                                                                    //    4255
                if ( $sale_price_dates_to < $to_sale_date ) {                                                                        //    4256
                    $to_sale_date = $sale_price_dates_to;                                                                            //    4257
                }                                                                                                                    //    4258
            } else {                                                                                                                 //    4259
                if ( is_numeric( $base->regular_price ) ) {                                                                          //    4260
                    if ( is_numeric( $sale_price ) ) {                                                                               //    4261
                        $sale_price += $base->regular_price;                                                                         //    4262
                    }                                                                                                                //    4263
                } else {                                                                                                             //    4264
                    $sale_price = '';                                                                                                //    4265
                }                                                                                                                    //    4266
                if ( is_numeric( $base->sale_price ) && ( $next_from_sale_date === NULL                                              //    4267
                        || $sale_price_dates_from < $next_from_sale_date ) ) {                                                       //    4268
                    $next_from_sale_date = $sale_price_dates_from;                                                                   //    4269
                }                                                                                                                    //    4270
            }                                                                                                                        //    4271
            if ( is_numeric( $base->regular_price ) ) {                                                                              //    4272
                if ( is_numeric ( $regular_price ) ) {                                                                               //    4273
                    $regular_price += $base->regular_price;                                                                          //    4274
                }                                                                                                                    //    4275
            } else {                                                                                                                 //    4276
                $regular_price = '';                                                                                                 //    4277
            }                                                                                                                        //    4278
        }   # foreach ( $bases as $base ) {                                                                                          //    4279
        if ( $next_from_sale_date !== NULL && $next_from_sale_date < $to_sale_date ) {                                               //    4280
            $to_sale_date = $next_from_sale_date;                                                                                    //    4281
        }                                                                                                                            //    4282
        if ( $from_sale_date === 0 ) {                                                                                               //    4283
            $from_sale_date = NULL;                                                                                                  //    4284
        }                                                                                                                            //    4285
        if ( $to_sale_date === PHP_INT_MAX ) {                                                                                       //    4286
            $to_sale_date = NULL;                                                                                                    //    4287
        }                                                                                                                            //    4288
        $product->set_sale_price(        $sale_price     );                                                                          //    4289
        $product->set_date_on_sale_from( $from_sale_date );                                                                          //    4290
        $product->set_date_on_sale_to(   $to_sale_date   );                                                                          //    4291
        if ( $update_database ) {                                                                                                    //    4292
            update_post_meta( $product->get_id(), '_regular_price',         $regular_price                                      );   //    4293
            update_post_meta( $product->get_id(), '_sale_price',            $sale_price < $regular_price ? $sale_price     : '' );   //    4294
            update_post_meta( $product->get_id(), '_sale_price_dates_from', $from_sale_date              ? $from_sale_date : '' );   //    4295
            update_post_meta( $product->get_id(), '_sale_price_dates_to',   $to_sale_date                ? $to_sale_date   : '' );   //    4296
            update_post_meta( $product->get_id(), '_stock',                 $stock                                              );   //    4297
        }                                                                                                                            //    4298
        $is_on_sale = $from_sale_date <= $now && $sale_price < $regular_price;                                                       //    4299
        $price = $is_on_sale ? $sale_price : $regular_price;                                                                         //    4300
        if ( $update_database ) {                                                                                                    //    4301
            update_post_meta( $product->get_id(), '_price',                       $price );                                          //    4302
            update_post_meta( $product->get_id(), '_mc_xii_sales_data_synced_at', current_time( 'timestamp', TRUE ) );               //    4303
        }                                                                                                                            //    4304
        $product->set_price( $price );                                                                                               //    4305
        return $is_on_sale;                                                                                                          //    4306
    }   # private static function calculate_sale_data_of_compound_variations_from_their_base_variations( $product, $bases = NULL,    //    4307
                                                                                                                                     //    4308

### REDACTED lines 4309 -> 5078 redacted,    770 lines redacted. ###

                                                                                                                                     //    5079
    public static function woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {                //    5080
        $extras = [];                                                                                                                //    5081
        if ( doing_action( 'wp_loaded' ) && ! empty( $_REQUEST['add-to-cart'] ) && is_numeric( $_REQUEST['add-to-cart'] ) ) {        //    5082
            # Called from inside WC_Form_Handler::add_to_cart_action().                                                              //    5083
            if ( MC_Simple_Variation_Functions::is_simple_variable( $product_id ) ) {                                                //    5084
                foreach ( $_REQUEST as $key => $value ) {                                                                            //    5085
                    if ( strpos( $key, 'attribute_' ) === 0 ) {                                                                      //    5086
                        # WC_Order_Item::get_formatted_meta_data() calls wc_is_attribute_in_product_name() and removes meta data     //    5087
                        # if its value is in the product name so modify the meta value                                               //    5088
                        $extras[ self::SIMPLE_VARIATION_DATA_PREFIX . substr( $key, 10 ) ] = '@#' . $value;                          //    5089
                    }                                                                                                                //    5090
                }                                                                                                                    //    5091
            }                                                                                                                        //    5092
        } else if ( doing_action( 'wp_ajax_woocommerce_add_to_cart' ) || doing_action( 'wp_ajax_nopriv_woocommerce_add_to_cart' )    //    5093
                || doing_action( 'wc_ajax_add_to_cart' ) ) {                                                                         //    5094
            # Called from inside WC_AJAX::add_to_cart().                                                                             //    5095
            if ( $variation_id ) {                                                                                                   //    5096
                if ( $variation = wc_get_product( $variation_id ) ) {                                                                //    5097
                    if ( MC_Simple_Variation_Functions::is_simple_variable( $variation->get_parent_id() ) ) {                        //    5098
                        foreach ( wc_get_product( $variation_id )->get_variation_attributes() as $attribute ) {                      //    5099
                            // TODO: $extras[] =                                                                                     //    5100
                        }                                                                                                            //    5101
                    }                                                                                                                //    5102
                }                                                                                                                    //    5103
            }                                                                                                                        //    5104
        }                                                                                                                            //    5105
        return array_merge( $cart_item_data, $extras );                                                                              //    5106
    }   # public static function woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {          //    5107
                                                                                                                                     //    5108
    # When a cart is created from an order our meta data in the order items is not automatically copied but the filter               //    5109
    # 'woocommerce_order_again_cart_item_data' can be used to copy our meta data from the order items into the cart items. See       //    5110
    # WC_Cart_Session::populate_cart_from_order().                                                                                   //    5111
    public static function woocommerce_order_again_cart_item_data( $cart_item_data, $item, $order ) {                                //    5112
        $meta_data = $item->get_meta_data();                                                                                         //    5113
        $extras = [];                                                                                                                //    5114
        foreach ( $meta_data as $meta ) {                                                                                            //    5115
            if ( substr_compare( $meta->key, self::SIMPLE_VARIATION_DATA_PREFIX, 0, 7 ) === 0 ) {                                    //    5116
                $extras[ $meta->key ] = $meta->value;                                                                                //    5117
            }                                                                                                                        //    5118
        }                                                                                                                            //    5119
        return array_merge( $cart_item_data, $extras );                                                                              //    5120
    }   # public static function woocommerce_order_again_cart_item_data( $cart_item_data, $item, $order ) {                          //    5121
                                                                                                                                     //    5122
    public static function woocommerce_add_cart_item( $cart_item_data, $key, $alt_key = NULL ) {                                     //    5123
        if ( $alt_key !== NULL ) {                                                                                                   //    5124
            $key = $alt_key;                                                                                                         //    5125
        }                                                                                                                            //    5126
        $product = $cart_item_data[ 'data' ];                                                                                        //    5127
        if ( self::is_variation_of_simple_variable( $product ) ) {                                                                   //    5128
            foreach ( $cart_item_data as $item_key => $item_value ) {                                                                //    5129
                if ( substr_compare( $item_key, self::SIMPLE_VARIATION_DATA_PREFIX, 0, 7 ) === 0 ) {                                 //    5130
                    $product->add_meta_data( $item_key, $item_value, TRUE );                                                         //    5131
                }                                                                                                                    //    5132
            }                                                                                                                        //    5133
        }                                                                                                                            //    5134
        $product->add_meta_data( MC_Product_Variation::CART_ITEM_KEY, $key, TRUE );                                                  //    5135
        return $cart_item_data;                                                                                                      //    5137
    }   # public static function woocommerce_add_cart_item( $cart_item_data ) {                                                      //    5138
                                                                                                                                     //    5139
    public static function woocommerce_order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {                          //    5140
        foreach ( $formatted_meta as $meta ) {                                                                                       //    5141
            $meta->display_key   = str_replace( self::SIMPLE_VARIATION_DATA_PREFIX, '', $meta->display_key );                        //    5142
            $meta->display_value = str_replace( [ '@#', self::UNSELECTED ], [ '', self::$none_label ], $meta->display_value );       //    5143
        }                                                                                                                            //    5144
        return $formatted_meta;                                                                                                      //    5145
    }   # public static function woocommerce_order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {                    //    5146
                                                                                                                                     //    5147

### REDACTED lines 5148 -> 5420 redacted,    273 lines redacted. ###

                                                                                                                                     //    5421
}   # class MC_Simple_Variation_Functions {                                                                                          //    5422
                                                                                                                                     //    5423
MC_Simple_Variation_Functions::init();                                                                                               //    5424
                                                                                                                                     //    5425
add_action( 'admin_init', function() {                                                                                               //    5426
    MC_Simple_Variation_Functions::admin_init();                                                                                     //    5427
} );                                                                                                                                 //    5428
                                                                                                                                     //    5429

### REDACTED lines 5430 -> 5521 redacted,     92 lines redacted. ###

                                                                                                                                     //    5522
class MC_Product_Attribute extends WC_Product_Attribute {                                                                            //    5523
                                                                                                                                     //    5524
    public static function init() {                                                                                                  //    5525
        add_filter( 'woocommerce_product_attribute_class',                                                                           //    5526
                    'MC_Product_Attribute::woocommerce_product_attribute_class', 10, 2 );                                            //    5527
    }                                                                                                                                //    5528
                                                                                                                                     //    5529
    public static function admin_init() {                                                                                            //    5530
        add_filter( 'woocommerce_admin_meta_boxes_prepare_attribute',                                                                //    5531
                    'MC_Product_Attribute::woocommerce_admin_meta_boxes_prepare_attribute', 10, 3 );                                 //    5532
        add_action( 'woocommerce_after_product_attribute_settings',                                                                  //    5533
                    'MC_Product_Attribute::woocommerce_after_product_attribute_settings', 10, 2 );                                   //    5534
    }                                                                                                                                //    5535
                                                                                                                                     //    5536
    public static function woocommerce_product_attribute_class( $classname, $data_or_product ) {                                     //    5537
        if ( $data_or_product instanceof WC_Product ) {                                                                              //    5538
            $product = $data_or_product;                                                                                             //    5539
        } else {                                                                                                                     //    5541
            $data = $data_or_product;                                                                                                //    5542
        }                                                                                                                            //    5544
        return $classname;                                                                                                           //    5545
    }                                                                                                                                //    5546
                                                                                                                                     //    5547
    // TODO: woocommerce_admin_meta_boxes_prepare_attribute() has support for backward compatibility with                            //    5548
    // TODO: MC_Simple_Variation_Functions::prepare_request_product_attributes() - remove that support after                         //    5549
    // TODO: MC_Product_simple_Variation::prepare_request_product_attributes() is removed.                                           //    5550
    public static function woocommerce_admin_meta_boxes_prepare_attribute( $attribute, $data, $i ) {                                 //    5551
        if ( ! empty( $_POST[ 'product-type' ] ) && $_POST[ 'product-type' ] !== 'simple-variable' ) {                               //    5554
            return $attribute;                                                                                                       //    5555
        }                                                                                                                            //    5556
        $post_id = (integer) ( ! empty( $_POST[ 'post_ID' ] ) ? $_POST[ 'post_ID' ]                                                  //    5557
                                                              : ( ! empty( $_POST[ 'post_id' ] ) ? $_POST[ 'post_id' ] : 0 ) );      //    5558
        if ( ! MC_Simple_Variation_Functions::is_simple_variable( $post_id ) ) {                                                     //    5560
            return $attribute;                                                                                                       //    5561
        }                                                                                                                            //    5562
        $options = $attribute->get_options();                                                                                        //    5563
        $new_attribute = new MC_Product_Attribute( $attribute );                                                                     //    5565
        $new_attribute->set_visible( 1 );                                                                                            //    5566
        $new_attribute->set_variation( 1 );                                                                                          //    5567
        if ( ! in_array( MC_Simple_Variation_Functions::UNSELECTED, $options ) ) {                                                   //    5568
            array_unshift( $options, MC_Simple_Variation_Functions::UNSELECTED );                                                    //    5569
            $new_attribute->set_options( $options );                                                                                 //    5570
        }                                                                                                                            //    5571
        $new_attribute->set_optional( ! empty( $data[ 'attribute_optional' ][ $i ] ) ? 1 : 0 );                                      //    5572
        // TODO: Below is hack for backward compatibility.                                                                           //    5573
        $new_attribute->set_optional(                                                                                                //    5574
                MC_Simple_Variation_Functions::is_optional_attribute_obsolete( $data[ 'attribute_names' ][ $i ] ) ? 1 : 0 );         //    5575
        return $new_attribute;                                                                                                       //    5577
    }   # public static function woocommerce_admin_meta_boxes_prepare_attribute( $attribute, $data, $i ) {                           //    5578
                                                                                                                                     //    5579
    public static function woocommerce_after_product_attribute_settings( $attribute, $i ) {                                          //    5580
        $checked = $attribute instanceof MC_Product_Attribute ? $attribute->get_optional() : FALSE;                                  //    5583
?>                                                                                                                                   <!--  5584 -->
<tr class="mc_xii-attribute_optional">                                                                                               <!--  5585 -->
    <td>                                                                                                                             <!--  5586 -->
        <div class="enable_optional enable_variation show_if_simple-variable">                                                       <!--  5587 -->
            <label>                                                                                                                  <!--  5588 -->
                <input type="checkbox" class="checkbox" <?php checked( $checked, true ); ?>                                          <!--  5589 -->
                        name="attribute_optional[<?php echo esc_attr( $i ); ?>]" value="1" />                                        <!--  5590 -->
                <?php echo esc_html( ucfirst( MC_Simple_Variation_Functions::$optional_label ) ); ?>                                 <!--  5591 -->
            </label>                                                                                                                 <!--  5592 -->
        </div>                                                                                                                       <!--  5593 -->
    </td>                                                                                                                            <!--  5594 -->
</tr>                                                                                                                                <!--  5595 -->
<?php                                                                                                                                //    5596
    }   # public static function woocommerce_after_product_attribute_settings( $attribute, $i ) {                                    //    5597
                                                                                                                                     //    5598
    public function __construct( $attribute ) {                                                                                      //    5599
        $this->set_id(        $attribute->get_id()        );                                                                         //    5600
        $this->set_name(      $attribute->get_name()      );                                                                         //    5601
        $this->set_options(   $attribute->get_options()   );                                                                         //    5602
        $this->set_position(  $attribute->get_position()  );                                                                         //    5603
        $this->set_visible(   $attribute->get_visible()   );                                                                         //    5604
        $this->set_variation( $attribute->get_variation() );                                                                         //    5605
        $this->set_optional(  0 );                                                                                                   //    5606
    }   # public function __construct( $attribute ) {                                                                                //    5607
                                                                                                                                     //    5608
    public function set_optional( $value ) {                                                                                         //    5609
        $this->data[ 'optional' ] = wc_string_to_bool( $value );                                                                     //    5610
    }                                                                                                                                //    5611
                                                                                                                                     //    5612
    public function get_optional() {                                                                                                 //    5613
        return $this->data[ 'optional' ];                                                                                            //    5614
    }                                                                                                                                //    5615
                                                                                                                                     //    5616
    public function get_data() {                                                                                                     //    5618
        return array_merge( parent::get_data(), [ 'is_optional' => $this->get_optional() ? 1 : 0 ] );                                //    5619
    }                                                                                                                                //    5620
                                                                                                                                     //    5621
    # The MC_Product_Attribute object is only available after instantiating its owner MC_Product_Simple_Variable object. This can be //    5622
    # very expensive when scanning all products. is_optional_attribute_from_database() directly reads the database to determine if   //    5623
    # an attribute is optional to do this more efficiently.                                                                          //    5624
    public static function is_optional_attribute_from_database( $attribute, $product_id ) {                                          //    5625
        $canonicalized_attribute = substr_compare( $attribute, 'attribute_', 0, 10 ) === 0 ? substr( $attribute, 10 )                //    5626
                                                                                           : $attribute;                             //    5627
        $attributes = get_post_meta( $product_id, '_product_attributes', TRUE );                                                     //    5628
        if ( is_array( $attributes ) && array_key_exists( $canonicalized_attribute, $attributes )                                    //    5629
                && array_key_exists( 'is_optional', $attributes[ $canonicalized_attribute ] ) ) {                                    //    5630
            return (boolean) $attributes[ $canonicalized_attribute ][ 'is_optional' ];                                               //    5631
        }                                                                                                                            //    5632
        return FALSE;                                                                                                                //    5633
    }   # public static function is_optional_attribute_from_database( $attribute, $product_id ) {                                    //    5634
                                                                                                                                     //    5635
}   # class MC_Product_Attribute extends WC_Product_Attribute {                                                                      //    5636
                                                                                                                                     //    5637
add_action( 'init', function() {                                                                                                     //    5638
    MC_Product_Attribute::init();                                                                                                    //    5639
} );                                                                                                                                 //    5640
                                                                                                                                     //    5641
add_action( 'admin_init', function() {                                                                                               //    5642
    MC_Product_Attribute::admin_init();                                                                                              //    5643
} );                                                                                                                                 //    5644
                                                                                                                                     //    5645
class MC_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT {                                                                  //    5646
                                                                                                                                     //    5647

### REDACTED lines 5648 -> 5655 redacted,      8 lines redacted. ###

                                                                                                                                     //    5656
    public static function init() {                                                                                                  //    5657
        # filter 'woocommerce_product_data_store' is applied in WC_Data_Store::__construct()                                         //    5658
        MC_Utility::add_filter( 'woocommerce_product_data_store', 'mc_xii_woocommerce_product_data_store',                           //    5659
                function( $store ) {                                                                                                 //    5660
            return new MC_Product_Data_Store_CPT();                                                                                  //    5661
        } );                                                                                                                         //    5662
    }                                                                                                                                //    5663
                                                                                                                                     //    5664

### REDACTED lines 5665 -> 5941 redacted,    277 lines redacted. ###

                                                                                                                                     //    5942
    # Classic Commerce's wc_scheduled_sales() should not process Simple Variation products so omit them from get_starting_sales()    //    5943
                                                                                                                                     //    5944
    public function get_starting_sales() {                                                                                           //    5946
        global $wpdb;                                                                                                                //    5947
        return $this->_get_starting_sales( "AND NOT EXISTS ( SELECT * FROM {$wpdb->postmeta} as postmeta_type                        //    5948
                                                WHERE postmeta_type.post_id = postmeta.post_id                                       //    5949
                                                    AND postmeta_type.meta_key = '_mc_xii_variation_type' )" );                      //    5950
    }   # public function get_starting_sales() {                                                                                     //    5951
                                                                                                                                     //    5952
    # Classic Commerce's wc_scheduled_sales() should not process Simple Variation products so omit them from get_ending_sales()      //    5953
                                                                                                                                     //    5954
    public function get_ending_sales() {                                                                                             //    5956
        global $wpdb;                                                                                                                //    5957
        return $this->_get_ending_sales( "AND NOT EXISTS ( SELECT * FROM {$wpdb->postmeta} as postmeta_type                          //    5958
                                              WHERE postmeta_type.post_id = postmeta.post_id                                         //    5959
                                                  AND postmeta_type.meta_key = '_mc_xii_variation_type' )" );                        //    5960
    }   # public function get_ending_sales() {                                                                                       //    5961
                                                                                                                                     //    5962
    # sv_get_starting_sales() gets base variations where sales are beginning                                                         //    5963
                                                                                                                                     //    5964
    public function sv_get_starting_sales() {                                                                                        //    5965
        global $wpdb;                                                                                                                //    5966
        $type_base = MC_Simple_Variation_Functions::TYPE_BASE;                                                                       //    5967
        return $this->_get_starting_sales( "AND EXISTS ( SELECT * FROM {$wpdb->postmeta} as postmeta_type                            //    5968
                                                WHERE postmeta_type.post_id = postmeta.post_id                                       //    5969
                                                    AND postmeta_type.meta_key = '_mc_xii_variation_type'                            //    5970
                                                    AND postmeta_type.meta_value = '{$type_base}' )" );                              //    5971
    }   # public function sv_get_starting_sales() {                                                                                  //    5972
                                                                                                                                     //    5973
    # sv_get_ending_sales() gets base variations where sales are ending                                                              //    5974
                                                                                                                                     //    5975
    public function sv_get_ending_sales() {                                                                                          //    5976
        global $wpdb;                                                                                                                //    5977
        $type_base = MC_Simple_Variation_Functions::TYPE_BASE;                                                                       //    5978
        return $this->_get_ending_sales( "AND EXISTS ( SELECT * FROM {$wpdb->postmeta}  as postmeta_type                             //    5979
                                                WHERE postmeta_type.post_id = postmeta.post_id                                       //    5980
                                                    AND postmeta_type.meta_key = '_mc_xii_variation_type'                            //    5981
                                                    AND postmeta_type.meta_value = '{$type_base}' )" );                              //    5982
    }   # public function sv_get_ending_sales() {                                                                                    //    5983
                                                                                                                                     //    5984
    private function _get_starting_sales( $clause ) {                                                                                //    5985
        global $wpdb;                                                                                                                //    5986
        return $wpdb->get_col(                                                                                                       //    5987
            $wpdb->prepare(                                                                                                          //    5988
                "SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta                                                          //    5989
                LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id                                   //    5990
                LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id                                   //    5991
                WHERE postmeta.meta_key = '_sale_price_dates_from'                                                                   //    5992
                    AND postmeta_2.meta_key = '_price'                                                                               //    5993
                    AND postmeta_3.meta_key = '_sale_price'                                                                          //    5994
                    AND postmeta.meta_value > 0                                                                                      //    5995
                    AND postmeta.meta_value < %s                                                                                     //    5996
                    AND postmeta_2.meta_value != postmeta_3.meta_value                                                               //    5997
                    {$clause}",                                                                                                      //    5998
                current_time( 'timestamp', true )                                                                                    //    5999
            )                                                                                                                        //    6000
        );                                                                                                                           //    6001
    }   # private function _get_starting_sales( $clause ) {                                                                          //    6002
                                                                                                                                     //    6003
    private function _get_ending_sales( $clause ) {                                                                                  //    6004
        global $wpdb;                                                                                                                //    6005
        return $wpdb->get_col(                                                                                                       //    6006
            $wpdb->prepare(                                                                                                          //    6007
                "SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta                                                          //    6008
                LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id                                   //    6009
                LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id                                   //    6010
                WHERE postmeta.meta_key = '_sale_price_dates_to'                                                                     //    6011
                    AND postmeta_2.meta_key = '_price'                                                                               //    6012
                    AND postmeta_3.meta_key = '_regular_price'                                                                       //    6013
                    AND postmeta.meta_value > 0                                                                                      //    6014
                    AND postmeta.meta_value < %s                                                                                     //    6015
                    AND postmeta_2.meta_value != postmeta_3.meta_value                                                               //    6016
                    {$clause}",                                                                                                      //    6017
                current_time( 'timestamp', true )                                                                                    //    6018
            )                                                                                                                        //    6019
        );                                                                                                                           //    6020
    }   #  private function _get_ending_sales( $clause ) {                                                                           //    6021
                                                                                                                                     //    6022
}   # class MC_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT {                                                            //    6023
                                                                                                                                     //    6024
MC_Product_Data_Store_CPT::init();                                                                                                   //    6025
                                                                                                                                     //    6026
class MC_Product_Variable_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT {                                                //    6027
                                                                                                                                     //    6028
    public static function init() {                                                                                                  //    6029
        MC_Utility::add_filter( 'woocommerce_product-variable_data_store', 'mc_xii_woocommerce_product-variable_data_store',         //    6030
                function( $store ) {                                                                                                 //    6031
            return new MC_Product_Variable_Data_Store_CPT();                                                                         //    6032
        } );                                                                                                                         //    6033
    }                                                                                                                                //    6034
                                                                                                                                     //    6035
    public function read_children( &$product, $force_read = false ) {                                                                //    6037
        $children = parent::read_children( $product, $force_read );                                                                  //    6041
        # Remove base variations if $product is a Simple Variable product.                                                           //    6042
        if ( $this->is_simple_variable( $product ) ) {                                                                               //    6043
            # Remove the base variations from $children[ 'all' ] and $children[ 'visible' ] as these variations are not really       //    6044
            # variations.                                                                                                            //    6045
            $base_variation_ids  = $this->get_base_variation_ids( $product );                                                        //    6046
            $children[ 'all' ]     = array_diff( $children[ 'all' ],     $base_variation_ids );                                      //    6047
            $children[ 'visible' ] = array_diff( $children[ 'visible' ], $base_variation_ids );                                      //    6048
            $attributes_min = [];                                                                                                    //    6049
            $attributes_max = [];                                                                                                    //    6050
            $prices = $this->get_min_max_prices( $product );                                                                         //    6051
            foreach ( $prices as $attribute => &$price ) {                                                                           //    6052
                if ( MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {            //    6053
                    $price[ 'min'     ] = 0.0;                                                                                       //    6054
                    $price[ 'min_val' ] = MC_Simple_Variation_Functions::UNSELECTED;                                                 //    6055
                }                                                                                                                    //    6056
                $attributes_min[ 'attribute_' . $attribute ] = $price[ 'min_val' ];                                                  //    6057
                $attributes_max[ 'attribute_' . $attribute ] = $price[ 'max_val' ];                                                  //    6058
            }                                                                                                                        //    6059
            if ( get_post_meta( $product->get_id(), '_mc_xii_product_attributes_version_count', TRUE ) ) {                           //    6063
                # If the variations are virtual then they do not exists in the database and $children[ 'all' ] and                   //    6064
                # $children[ 'visible' ] will be empty. Since, there may be a humongous number of virtual variations and             //    6065
                # WC_Product_Variable_Data_Store_CPT::sync_price() will create a database row in table $wpdb->postmeta for each      //    6066
                # variation in $children[ 'visible' ] we do not want to $children[ 'visible' ] to contain all of these. Instead just //    6067
                # add entries for least expensive and most expensive variations so database queries on price will at least get the   //    6068
                # right range.                                                                                                       //    6069
                $product_data_store    = new MC_Product_Data_Store_CPT();                                                            //    6070
                $variation_id_min      = $product_data_store->find_matching_product_variation( $product, $attributes_min );          //    6071
                $variation_id_max      = $product_data_store->find_matching_product_variation( $product, $attributes_max );          //    6072
                $children[ 'all' ]     = [ $variation_id_min, $variation_id_max ];                                                   //    6073
                $children[ 'visible' ] = [ $variation_id_min, $variation_id_max ];                                                   //    6074
            }   # if ( get_post_meta( $product_id, '_mc_xii_product_attributes_version_count', TRUE ) ) {                            //    6076
        }                                                                                                                            //    6077
        return $children;                                                                                                            //    6078
    }   # public function read_children( &$product, $force_read = false ) {                                                          //    6079
                                                                                                                                     //    6080
    public function read_all_children( &$product, $force_read = FALSE ) {                                                            //    6081
        return parent::read_children( $product, $force_read );                                                                       //    6082
    }   # public function read_all_children( &$product, $force_read = FALSE ) {                                                      //    6083
                                                                                                                                     //    6084
    public function is_simple_variable( $product ) {                                                                                 //    6085
        return ! ! get_post_meta( $product->get_id(), '_mc_xii_is_simple_variable', TRUE );                                          //    6086
    }   # public function is_simple_variable( $product ) {                                                                           //    6087
                                                                                                                                     //    6088
    public function get_base_variation_ids( $product ) {                                                                             //    6089
        global $wpdb;                                                                                                                //    6090
        $base_variation_ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                 //    6091
SELECT p.ID FROM $wpdb->posts p, $wpdb->postmeta m                                                                                   //    6092
    WHERE p.ID = m.post_id AND p.post_parent = %d AND p.post_type = 'product_variation' AND m.meta_key = '_mc_xii_variation_type'    //    6093
        AND m.meta_value = 'base'                                                                                                    //    6094
EOD                                                                                                                                  //    6095
                                                            , $product->get_id() ) );                                                //    6096
        return $base_variation_ids;                                                                                                  //    6097
    }   # public function get_base_variations_ids( $product ) {                                                                      //    6098
                                                                                                                                     //    6099
    protected function read_attributes( &$product ) {                                                                                //    6101
        parent::read_attributes( $product );                                                                                         //    6103
        if ( ! $product instanceof MC_Product_Simple_Variable ) {                                                                    //    6104
            return;                                                                                                                  //    6105
        }                                                                                                                            //    6106
        $attributes = $product->get_attributes();                                                                                    //    6107
        $meta_attributes = get_post_meta( $product->get_id(), '_product_attributes', TRUE );                                         //    6108
        $new_attributes = [];                                                                                                        //    6109
        foreach ( $attributes as $name => $attribute ) {                                                                             //    6110
            $new_attribute = new MC_Product_Attribute( $attribute );                                                                 //    6111
            if ( ! empty( $meta_attributes[ sanitize_title( $name ) ][ 'is_optional' ] ) ) {                                         //    6112
                $new_attribute->set_optional( 1 );                                                                                   //    6113
            }                                                                                                                        //    6114
            // TODO: Below is a hack for backward compatibility                                                                      //    6115
            if ( strlen( $name ) > MC_Simple_Variation_Functions::$optional_suffix_length && ! substr_compare( $name,                //    6116
                    MC_Simple_Variation_Functions::OPTIONAL, - MC_Simple_Variation_Functions::$optional_suffix_length ) ) {          //    6117
                $new_attribute->set_optional( 1 );                                                                                   //    6118
            }                                                                                                                        //    6119
            $new_attributes[] = $new_attribute;                                                                                      //    6120
        }                                                                                                                            //    6121
        $product->set_attributes( $new_attributes );                                                                                 //    6122
        $attributes = $product->get_attributes();                                                                                    //    6123
    }   # protected function read_attributes( &$product ) {                                                                          //    6124
                                                                                                                                     //    6125
    protected function update_attributes( &$product, $force = false ) {                                                              //    6127
        parent::update_attributes( $product, $force );                                                                               //    6129
        if ( ! $product instanceof MC_Product_Simple_Variable ) {                                                                    //    6130
            return;                                                                                                                  //    6131
        }                                                                                                                            //    6132
        $attributes  = $product->get_attributes();                                                                                   //    6133
        $meta_values = get_post_meta( $product->get_id(), '_product_attributes', TRUE );                                             //    6134
        if ( ! $meta_values ) {                                                                                                      //    6135
            return;                                                                                                                  //    6136
        }                                                                                                                            //    6137
        foreach ( $meta_values as $key => &$meta_value ) {                                                                           //    6138
            $attribute = $attributes[ sanitize_title( $key ) ];                                                                      //    6139
            $meta_value[ 'is_optional' ] = $attribute->get_optional() ? 1 : 0;                                                       //    6140
        }                                                                                                                            //    6141
        update_post_meta( $product->get_id(), '_product_attributes', $meta_values );                                                 //    6142
    }   # protected function update_attributes( &$product, $force = false ) {                                                        //    6143
                                                                                                                                     //    6144
    # WC_Product_Variable_Data_Store_CPT::read_price_data() uses the variations to calculate the variation prices for a variable     //    6145
    # product. If the variable product uses virtual variation these variations do not exists so the variation prices must be         //    6146
    # calculated dynamically from the base variation prices.                                                                         //    6147
                                                                                                                                     //    6148
    public function read_price_data( &$product, $for_display = FALSE ) {                                                             //    6150
        global $wpdb;                                                                                                                //    6151
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $product->get_id() ) ) {                                              //    6152
            return parent::read_price_data( $product, $for_display );                                                                //    6153
        }                                                                                                                            //    6154
        $hide_out_of_stock_sql_0 = $hide_out_of_stock_sql_1 = $hide_out_of_stock_sql_2 = '';                                         //    6155
        if ( wc_string_to_bool( get_option( 'woocommerce_hide_out_of_stock_items' ) ) ) {                                            //    6156
            $hide_out_of_stock_sql_0 = "$wpdb->postmeta m2,";                                                                        //    6157
            $hide_out_of_stock_sql_1 = 'AND m0.post_id = m2.post_id';                                                                //    6158
            $hide_out_of_stock_sql_2 = 'AND m2.meta_key = "_stock_status" AND m2.meta_value != "outofstock"';                        //    6159
        }                                                                                                                            //    6160
        $prices              = [];                                                                                                   //    6161
        $price_for_attribute = [];                                                                                                   //    6162
        $on_sale             = TRUE;                                                                                                 //    6163
        foreach ( [ '_price', '_regular_price' ] as $price_type ) {                                                                  //    6164
            $results = $wpdb->get_results(  $wpdb->prepare( <<<EOD                                                                   //    6165
SELECT m0.meta_key attribute, m0.meta_value value, m1.meta_value price FROM $wpdb->postmeta m0, $wpdb->postmeta m1,                  //    6166
    $hide_out_of_stock_sql_0 $wpdb->posts p                                                                                          //    6167
WHERE m0.post_id = m1.post_id AND m0.post_id = p.id $hide_out_of_stock_sql_1                                                         //    6168
    AND p.post_parent = %d AND p.post_type = 'product_variation' AND p.post_status = 'publish'                                       //    6169
    AND m0.meta_key LIKE 'attribute_%' AND m0.meta_value != 'mc_xii_not_selected' $hide_out_of_stock_sql_2                           //    6170
    AND m1.meta_key = '$price_type'                                                                                                  //    6171
EOD                                                                                                                                  //    6172
                , $product->get_id() ) );                                                                                            //    6173
            $min     = [];                                                                                                           //    6174
            $max     = [];                                                                                                           //    6175
            $min_key = [];                                                                                                           //    6176
            $max_key = [];                                                                                                           //    6177
            foreach ( $results as $result ) {                                                                                        //    6178
                $attribute = $result->attribute;                                                                                     //    6179
                $price     = $result->price;                                                                                         //    6180
                $key       = $result->value;                                                                                         //    6181
                if ( ! is_numeric( $price ) ) {                                                                                      //    6182
                    continue;                                                                                                        //    6183
                }                                                                                                                    //    6184
                if ( $price_type === '_price' ) {                                                                                    //    6185
                    $price_for_attribute[ $attribute ] = $price;                                                                     //    6186
                } else {                                                                                                             //    6187
                    $on_sale &= $price_for_attribute[ $attribute ] < $price;                                                         //    6188
                }                                                                                                                    //    6189
                if ( ! array_key_exists( $attribute, $max ) ) {                                                                      //    6190
                    if ( ! MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {      //    6191
                        $min[ $attribute ]     = $price;                                                                             //    6192
                        $min_key[ $attribute ] = $key;                                                                               //    6193
                    } else {                                                                                                         //    6194
                        $min[ $attribute ]     = 0;                                                                                  //    6195
                        $min_key[ $attribute ] = MC_Simple_Variation_Functions::UNSELECTED;                                          //    6196
                    }                                                                                                                //    6197
                    $max[ $attribute ]     = $price;                                                                                 //    6198
                    $max_key[ $attribute ] = $key;                                                                                   //    6199
                } else {                                                                                                             //    6200
                    if ( ! MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {      //    6201
                        if ( $price < $min[ $attribute ] ) {                                                                         //    6202
                            $min[ $attribute ]     = $price;                                                                         //    6203
                            $min_key[ $attribute ] = $key;                                                                           //    6204
                        }                                                                                                            //    6205
                    }                                                                                                                //    6206
                    if ( $price > $max[ $attribute ] ) {                                                                             //    6207
                        $max[ $attribute ]     = $price;                                                                             //    6208
                        $max_key[ $attribute ] = $key;                                                                               //    6209
                    }                                                                                                                //    6210
                }                                                                                                                    //    6211
            }                                                                                                                        //    6212
            # Verify that all component types have at least one component in stock.                                                  //    6213
            if ( count( $min_key ) < count( $product->get_variation_attributes() ) ) {                                               //    6214
                return [ 'price' => [], 'regular_price' => [], 'sale_price' => [] ];                                                 //    6215
            }                                                                                                                        //    6216
            $min_price = 0;                                                                                                          //    6217
            foreach ( $min as $value ) {                                                                                             //    6218
                $min_price += $value;                                                                                                //    6219
            }                                                                                                                        //    6220
            $max_price = 0;                                                                                                          //    6221
            foreach ( $max as $value ) {                                                                                             //    6222
                $max_price += $value;                                                                                                //    6223
            }                                                                                                                        //    6224
            $data_store       = WC_Data_Store::load( 'product' );                                                                    //    6225
            $min_variation_id = $data_store->find_matching_product_variation( $product, $min_key );                                  //    6226
            $max_variation_id = $data_store->find_matching_product_variation( $product, $max_key );                                  //    6227
            $prices[ trim( $price_type, '_' ) ] = [ $min_variation_id => $min_price, $max_variation_id => $max_price ];              //    6228
            # TODO: _sale_price                                                                                                      //    6229
        }   # foreach ( [ '_price', '_regular_price' ] as $price_type ) {                                                            //    6230
        # refer to is_on_WC_Product_Variable::sale() for why $prices['sale_price'] is set as follows                                 //    6231
        $prices['sale_price'] = $on_sale ? $prices['price'] : $prices['regular_price'];                                              //    6232
        # following derived from WC_Product_Variable_Data_Store_CPT::read_price_data()                                               //    6233
        if ( $for_display ) {                                                                                                        //    6234
            $incl = get_option( 'woocommerce_tax_display_shop' ) === 'incl';                                                         //    6235
            $args = [ 'qty' => 1 ];                                                                                                  //    6236
            foreach ( [ 'price', 'regular_price', 'sale_price' ] as $price_type ) {                                                  //    6237
                foreach ( $prices[ $price_type ] as $variation_id => &$price ) {                                                     //    6238
                    if ( $price !== '' ) {                                                                                           //    6239
                        $variation     = wc_get_product( $variation_id );                                                            //    6240
                        $args['price'] = $price;                                                                                     //    6241
                        $price         = $incl ? wc_get_price_including_tax( $variation, $args )                                     //    6242
                                               : wc_get_price_excluding_tax( $variation, $args );                                    //    6243
                    }                                                                                                                //    6244
                    $price = wc_format_decimal( $price, wc_get_price_decimals() );                                                   //    6245
                }   # foreach ( $prices[ $price_type ] as &$price ) {                                                                //    6246
            }   # foreach ( [ 'price', 'regular_price', 'sale_price' ] as $price_type ) {                                            //    6247
        }   # if ( $for_display ) {                                                                                                  //    6248
        return $prices;                                                                                                              //    6249
    }   # public function read_price_data( &$product, $for_display = FALSE ) {                                                       //    6250
                                                                                                                                     //    6251
    public function sync_price( &$product ) {                                                                                        //    6253
        if ( MC_Product_Data_Store_CPT::is_virtual_variable( $product->get_id() ) ) {                                                //    6254
            $this->sv_sync_price( $product );                                                                                        //    6255
            return;                                                                                                                  //    6256
        }                                                                                                                            //    6257
        parent::sync_price( $product );                                                                                              //    6258
    }   # public function sync_price( &$product ) {                                                                                  //    6259
                                                                                                                                     //    6260
    # WC_Product_Variable_Data_Store_CPT::sync_price() creates a row with meta_key == '_price' in $wpdb->postmeta for every          //    6261
    # variation. Since, virtual variable products may have a humongous number of variations this is not practical. Rather,           //    6262
    # with respect to how Classic Commerce currently uses these rows it is sufficient just to have rows for the mininum and          //    6263
    # maximum price. See WC_Product_Variable::get_variation_prices().                                                                //    6264
                                                                                                                                     //    6265
    private function sv_sync_price( &$product ) {                                                                                    //    6266
        $prices = $product->get_min_max_price();                                                                                     //    6268
        $min    = $prices[ 'min' ];                                                                                                  //    6269
        $max    = $prices[ 'max' ];                                                                                                  //    6270
        delete_post_meta( $product->get_id(), '_price' );                                                                            //    6272
        delete_post_meta( $product->get_id(), '_sale_price' );                                                                       //    6273
        delete_post_meta( $product->get_id(), '_regular_price' );                                                                    //    6274
        if ( $min !== '' ) {                                                                                                         //    6275
            add_post_meta( $product->get_id(), '_price', $min, false );                                                              //    6276
        }                                                                                                                            //    6277
        if ( $max !== '' ) {                                                                                                         //    6278
            add_post_meta( $product->get_id(), '_price', $max, false );                                                              //    6279
        }                                                                                                                            //    6280
    }   # private function sv_sync_price( &$product ) {                                                                              //    6281
                                                                                                                                     //    6282
    public function get_min_max_prices( &$product, $flags = [] ) {                                                                   //    6283
        global $wpdb;                                                                                                                //    6284
        $in_stock_only = in_array( 'in_stock', $flags );                                                                             //    6286
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                        //    6287
SELECT m.post_id, m.meta_key, m.meta_value                                                                                           //    6288
    FROM $wpdb->postmeta m, $wpdb->posts p, $wpdb->postmeta n                                                                        //    6289
    WHERE m.post_id = p.ID AND n.post_id = p.ID                                                                                      //    6290
        AND p.post_parent = %d AND p.post_status = 'publish'                                                                         //    6291
        AND n.meta_key = '_mc_xii_variation_type' AND n.meta_value = 'base'                                                          //    6292
        AND (m.meta_key LIKE 'attribute_%' OR m.meta_key LIKE '_%price')                                                             //    6293
EOD                                                                                                                                  //    6294
                                                     , $product->get_id() ) );                                                       //    6295
        $in_stock_ids = [];                                                                                                          //    6297
        if ( $in_stock_only ) {                                                                                                      //    6298
        $in_stock_ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                       //    6299
SELECT m.post_id                                                                                                                     //    6300
    FROM $wpdb->postmeta m, $wpdb->posts p, $wpdb->postmeta n                                                                        //    6301
    WHERE m.post_id = p.ID AND n.post_id = p.ID                                                                                      //    6302
        AND p.post_parent = %d AND p.post_status = 'publish'                                                                         //    6303
        AND n.meta_key = '_mc_xii_variation_type' AND n.meta_value = 'base'                                                          //    6304
        AND m.meta_key = '_stock_status' AND m.meta_value = 'instock'                                                                //    6305
EOD                                                                                                                                  //    6306
                                                      , $product->get_id() ) );                                                      //    6307
        }                                                                                                                            //    6308
        $data = [];                                                                                                                  //    6310
        foreach ( $results as $result ) {                                                                                            //    6311
            $post_id      = $result->post_id;                                                                                        //    6312
            $key          = $result->meta_key;                                                                                       //    6313
            $value        = $result->meta_value;                                                                                     //    6314
            $not_in_stock = $in_stock_only && ! in_array( $post_id, $in_stock_ids );                                                 //    6315
            if ( ! array_key_exists( $post_id, $data ) ) {                                                                           //    6316
                $data[ $post_id ] = [ 'attribute' => null, 'value' => null, 'price' => null ];                                       //    6317
            }                                                                                                                        //    6318
            $datum =& $data[ $post_id ];                                                                                             //    6319
            if ( substr_compare( $key, 'attribute_', 0, 10 ) === 0 && $value !== 'mc_xii_not_selected' ) {                           //    6320
                $datum[ 'attribute' ] = substr( $key, 10 );                                                                          //    6321
                $datum[ 'value'     ] = $value;                                                                                      //    6322
            }                                                                                                                        //    6323
            if ( $key === '_price' ) {                                                                                               //    6324
                $datum[ 'price' ] = $not_in_stock ? '' : $value;                                                                     //    6325
            }                                                                                                                        //    6326
            unset( $datum );                                                                                                         //    6327
        }                                                                                                                            //    6328
        $prices = [];                                                                                                                //    6330
        foreach ( $data as $post_id => $datum ) {                                                                                    //    6331
            $attribute = $datum[ 'attribute' ];                                                                                      //    6332
            $value     = $datum[ 'value' ];                                                                                          //    6333
            $price     = $datum[ 'price' ];                                                                                          //    6334
            $price     = is_null( $price ) || '' === $price ? '' : (float) $price;                                                   //    6335
            if ( ! array_key_exists( $attribute, $prices ) ) {                                                                       //    6336
                $prices[ $attribute ] = [ 'min' => PHP_INT_MAX, 'max' => PHP_INT_MIN, 'min_val' => '', 'max_val' => '' ];            //    6337
            }                                                                                                                        //    6338
            $prices_for_attribute =& $prices[ $attribute ];                                                                          //    6339
            if ( $price !== '' && $price < $prices_for_attribute[ 'min' ] ) {                                                        //    6340
                $prices_for_attribute[ 'min' ]     = $price;                                                                         //    6341
                $prices_for_attribute[ 'min_val' ] = $value;                                                                         //    6342
            }                                                                                                                        //    6343
            if ( $price !== '' && $price > $prices_for_attribute[ 'max' ] ) {                                                        //    6344
                $prices_for_attribute[ 'max' ]     = $price;                                                                         //    6345
                $prices_for_attribute[ 'max_val' ] = $value;                                                                         //    6346
            }                                                                                                                        //    6347
            unset( $prices_for_attribute );                                                                                          //    6348
        }                                                                                                                            //    6349
        foreach ( $prices as &$prices_for_attribute ) {                                                                              //    6350
            foreach ( $prices_for_attribute as &$price ) {                                                                           //    6351
                if ( $price === PHP_INT_MAX || $price === PHP_INT_MIN ) {                                                            //    6352
                    $price = '';                                                                                                     //    6353
                }                                                                                                                    //    6354
            }                                                                                                                        //    6355
            unset( $price );                                                                                                         //    6356
        }                                                                                                                            //    6357
        unset( $prices_for_attribute );                                                                                              //    6358
        return $prices;                                                                                                              //    6360
    }   # public function get_min_max_prices( &$product ) {                                                                          //    6361
                                                                                                                                     //    6362
    public function child_is_in_stock( $product ) {                                                                                  //    6364
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $product->get_id() ) ) {                                              //    6366
            return parent::child_is_in_stock( $product );                                                                            //    6367
        }                                                                                                                            //    6368
        # Since virtual variations do not exists in the database parent::child_is_in_stock( $product ) will not work;                //    6370
        return is_numeric( $product->get_min_max_price()[ 'min' ] );                                                                 //    6371
    }   # public function child_is_in_stock( $product ) {                                                                            //    6372
                                                                                                                                     //    6373
    public function sync_managed_variation_stock_status( &$product ) {                                                               //    6375
        global $wpdb;                                                                                                                //    6376
        if ( $product->get_manage_stock() ) {                                                                                        //    6379
            $wpdb->query( $wpdb->prepare( <<<EOD                                                                                     //    6380
UPDATE $wpdb->postmeta m, $wpdb->posts p SET m.meta_value = 'no'                                                                     //    6381
    WHERE m.post_id = p.ID AND p.post_parent = %d AND (m.meta_key = '_manage_stock' OR m.meta_key = '_backorders')                   //    6382
EOD                                                                                                                                  //    6383
                                        , $product->get_id() ) );                                                                    //    6384
            $wpdb->query( $wpdb->prepare( <<<EOD                                                                                     //    6385
UPDATE $wpdb->postmeta m, $wpdb->posts p SET m.meta_value = ''                                                                       //    6386
    WHERE m.post_id = p.ID AND p.post_parent = %d AND (m.meta_key = '_stock' OR m.meta_key = '_low_stock_amount')                    //    6387
EOD                                                                                                                                  //    6388
                                        , $product->get_id() ) );                                                                    //    6389
        }                                                                                                                            //    6390
        parent::sync_managed_variation_stock_status( $product );                                                                     //    6391
    }   # public function sync_managed_variation_stock_status( &$product ) {                                                         //    6392
                                                                                                                                     //    6393
    public function get_number_of_variations( &$product ) {                                                                          //    6394
        $attributes = get_post_meta( $product->get_id(), '_product_attributes', TRUE );                                              //    6395
        $number     = 1;                                                                                                             //    6396
        foreach ( $attributes as $slug => $attribute ) {                                                                             //    6397
            $count = count( explode( '|', $attribute[ 'value' ] ) ) - 1;                                                             //    6398
            if ( $attribute[ 'is_optional' ] ) {                                                                                     //    6399
                ++$count;                                                                                                            //    6400
            }                                                                                                                        //    6401
            $number *= $count;                                                                                                       //    6402
        }                                                                                                                            //    6403
        return $number;                                                                                                              //    6404
    }   # public function get_number_of_variations( &$product ) {                                                                    //    6405
                                                                                                                                     //    6406
    # get_variation_counts() requires that all base variations exists in the database, i.e., it will not work immediately after a    //    6407
    # call to canonicalize_variations() as missing base variations are generated later in the first phase of the call to             //    6408
    # sync_compound_variations_with_base_variations().                                                                               //    6409
                                                                                                                                     //    6410
    public function get_variation_counts( $product_id ) {                                                                            //    6411
        global $wpdb;                                                                                                                //    6412
        $type_base = MC_Simple_Variation_Functions::TYPE_BASE;                                                                       //    6413
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                        //    6414
SELECT n.meta_key attribute, n.meta_value value FROM $wpdb->posts p, $wpdb->postmeta m, $wpdb->postmeta n                            //    6415
    WHERE p.post_parent = %d AND m.post_id = p.id AND n.post_id = p.id                                                               //    6416
        AND m.meta_key = '_mc_xii_variation_type' AND m.meta_value = '$type_base'                                                    //    6417
        AND n.meta_key LIKE 'attribute_%' AND n.meta_value !='mc_xii_not_selected'                                                   //    6418
    ORDER BY attribute                                                                                                               //    6419
EOD                                                                                                                                  //    6420
            , $product_id ) );                                                                                                       //    6421
        if ( ! $results ) {                                                                                                          //    6422
            return NULL;                                                                                                             //    6423
        }                                                                                                                            //    6424
        $counts                 = [];                                                                                                //    6425
        $optional_count         = 0;                                                                                                 //    6426
        foreach ( $results as $result ) {                                                                                            //    6427
            if ( array_key_exists( $result->attribute, $counts ) ) {                                                                 //    6428
                ++$counts[ $result->attribute ];                                                                                     //    6429
            } else {                                                                                                                 //    6430
                $counts[ $result->attribute ] = 1;                                                                                   //    6431
                if ( MC_Product_Attribute::is_optional_attribute_from_database( $result->attribute, $product_id ) ) {                //    6432
                    ++$counts[ $result->attribute ];                                                                                 //    6433
                    ++$optional_count;                                                                                               //    6434
                }                                                                                                                    //    6435
            }                                                                                                                        //    6436
        }                                                                                                                            //    6437
        $variation_counts      = 1;                                                                                                  //    6438
        $base_variation_counts = 0;                                                                                                  //    6439
        $pretty_counts         = [];                                                                                                 //    6440
        $optional              = [];                                                                                                 //    6441
        foreach ( $counts as $attribute => $count ) {                                                                                //    6442
            $variation_counts      *= $count;                                                                                        //    6443
            $base_variation_counts += $count;                                                                                        //    6444
            if ( MC_Product_Attribute::is_optional_attribute_from_database( $attribute, $product_id ) ) {                            //    6445
                $pretty_attribute                   = substr( $attribute, 10,                                                        //    6446
                                                              - MC_Simple_Variation_Functions::$optional_suffix_length );            //    6447
                $pretty_counts[ $pretty_attribute ] = $count - 1;                                                                    //    6448
                $optional[]                         = $pretty_attribute;                                                             //    6449
            } else {                                                                                                                 //    6450
                $pretty_counts[ substr( $attribute, 10 ) ] = $count;                                                                 //    6451
            }                                                                                                                        //    6452
        }                                                                                                                            //    6453
        $base_variation_counts -= $optional_count;                                                                                   //    6454
        return [ MC_Simple_Variation_Functions::TYPE_BASE => $base_variation_counts,                                                 //    6455
                 MC_Simple_Variation_Functions::TYPE_COMPOUND => $variation_counts,                                                  //    6456
                 'counts_by_attributes' => $pretty_counts,                                                                           //    6457
                 'optional' => $optional ];                                                                                          //    6458
    }   # public function get_variation_counts( $product_id ) {                                                                      //    6459
                                                                                                                                     //    6460
    public function get_base_variations_for_attributes( $product, &$attributes, &$attribute_values,                                  //    6461
                                                        &$map_attributes_to_variation ) {                                            //    6462
        $product = MC_Simple_Variation_Functions::get_product( $product, $post_id );                                                 //    6464
        MC_Simple_Variation_Functions::load_variations_from_database( $product, $attributes, $attribute_values,                      //    6465
                                                                    $map_attributes_to_variation );                                  //    6466
        $attribute_values_keys = array_keys( $attribute_values );                                                                    //    6467
        $base_variation_for_attribute = [];                                                                                          //    6468
        foreach ( $attribute_values_keys as $attribute ) {                                                                           //    6469
            foreach ( $attribute_values[ $attribute ] as $value ) {                                                                  //    6470
                if ( $value === MC_Simple_Variation_Functions::UNSELECTED ) {                                                        //    6471
                    continue;                                                                                                        //    6472
                }                                                                                                                    //    6473
                $variation = MC_Simple_Variation_Functions::get_base_variation_for_attribute( $post_id, $attribute, $value,          //    6474
                                 $map_attributes_to_variation, $attribute_values_keys, $the_attributes,                              //    6475
                                 $product->get_meta( '_mc_xii_version' ) );                                                          //    6476
                if ( empty( $base_variation_for_attribute[ $attribute ] ) ) {                                                        //    6477
                    $base_variation_for_attribute[ $attribute ] = [];                                                                //    6478
                }                                                                                                                    //    6479
                $base_variation_for_attribute[ $attribute ][ $value ] = $variation;                                                  //    6480
            }                                                                                                                        //    6481
        }                                                                                                                            //    6482
        return $base_variation_for_attribute;                                                                                        //    6483
    }   # public function get_base_variations_for_attributes( $product, &$attributes, &$attribute_values,                            //    6484
                                                                                                                                     //    6485
}   # class MC_Product_Variable_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT {                                          //    6486
                                                                                                                                     //    6487
MC_Product_Variable_Data_Store_CPT::init();                                                                                          //    6488
                                                                                                                                     //    6489
class MC_Product_Variation_Data_Store_CPT extends WC_Product_Variation_Data_Store_CPT {                                              //    6490
                                                                                                                                     //    6491

### REDACTED lines 6492 -> 6496 redacted,      5 lines redacted. ###

                                                                                                                                     //    6497
    public static function init() {                                                                                                  //    6498
        # filter 'woocommerce_product-variation_data_store' is applied in WC_Data_Store::__construct()                               //    6499
        MC_Utility::add_filter( 'woocommerce_product-variation_data_store', 'mc_xii_woocommerce_product-variation_data_store',       //    6500
                function( $store ) {                                                                                                 //    6501
            return new MC_Product_Variation_Data_Store_CPT();                                                                        //    6502
        } );                                                                                                                         //    6503
                                                                                                                                     //    6504

### REDACTED lines 6505 -> 6516 redacted,     12 lines redacted. ###

                                                                                                                                     //    6517
        MC_Hook_Wrapper::wrap_hook(                                                                                                  //    6518
                'wc_maybe_reduce_stock_levels',                                                                                      //    6519
                function( $callback, $order_id ) {                                                                                   //    6520
                    # provide order context for code lower down                                                                      //    6521
                    $context = new MC_Context( 'order_id', $order_id );                                                              //    6522
                    call_user_func( $callback, $order_id );                                                                          //    6523
                },                                                                                                                   //    6524
                [ 'woocommerce_payment_complete', 'woocommerce_order_status_completed', 'woocommerce_order_status_processing',       //    6525
                        'woocommerce_order_status_on-hold' ],                                                                        //    6526
                'wc_maybe_reduce_stock_levels', TRUE );                                                                              //    6527
        MC_Hook_Wrapper::wrap_hook(                                                                                                  //    6528
                'wc_maybe_increase_stock_levels',                                                                                    //    6529
                function( $callback, ...$args ) {                                                                                    //    6530
                    try {                                                                                                            //    6531
                        # provide order context for code lower down                                                                  //    6532
                        $i = MC_Context::push( 'order_id', $args[0] );                                                               //    6533
                        MC_Context::dump( 'wc_maybe_increase_stock_levels' );                                                        //    6534
                        call_user_func_array( $callback, $args );                                                                    //    6535
                    } finally {                                                                                                      //    6536
                        MC_Context::pop_to( $i );                                                                                    //    6537
                        MC_Context::dump( 'wc_maybe_increase_stock_levels' );                                                        //    6538
                    }                                                                                                                //    6539
                },                                                                                                                   //    6540
                [ 'woocommerce_order_status_cancelled', 'woocommerce_order_status_pending' ],                                        //    6541
                'wc_maybe_increase_stock_levels', TRUE );                                                                            //    6542
    }   # public static function init() {                                                                                            //    6543
                                                                                                                                     //    6544

### REDACTED lines 6545 -> 6803 redacted,    259 lines redacted. ###

                                                                                                                                     //    6804
    # canonicalize_variations() adjust existing variations to be consistent with the product attributes of its parent variable       //    6805
    # product. Classic Commerce does this lazily only when the variation is updated using                                            //    6806
    # WC_Product_Variation_Data_Store_CPT::update_attributes() which is called from WC_Product_Variation_Data_Store_CPT::update().   //    6807
    # However, this can cause WC_Product_Data_Store_CPT::find_matching_product_variation to return incorrect results as it runs      //    6808
    # directly off the database. This of course is a transient problem as the user will probably manually update the corresponding   //    6809
    # variations soon after updating the attributes. However, canonicalize_variations() will automatically update the database       //    6810
    # immediately after the new attributes are saved. canonicalize_variations() only handles existing variations. If new variations  //    6811
    # are needed they will be generate later by MC_Simple_Variation_Functions::sync_compound_variations_with_base_variations().      //    6812
    # N.B. For simple variable products with a huge number of variations canonicalize_variations() may not be able to update all     //    6813
    # the variations in a single call as the PHP execution time limit may be exceeded. Multiple calls may be necessary and           //    6814
    # subsequent calls must set $continue to TRUE. canonicalize_variations() should return FALSE if it cannot process all the        //    6815
    # variations. The return value is only used to send messages to the frontend. The critical state data is always saved in the     //    6816
    # database so recovery can be done in the event the session is terminated because the PHP execution time limit is exceeded.      //    6817
                                                                                                                                     //    6818
    public function canonicalize_variations( $product, $continue = FALSE ) {                                                         //    6819
        global $wpdb;                                                                                                                //    6820
        $product = MC_Simple_Variation_Functions::get_product( $product, $post_id );                                                 //    6822
        if ( MC_Simple_Variation_Functions::is_malconfigured( $post_id ) ) {                                                         //    6823
            return TRUE;                                                                                                             //    6824
        }                                                                                                                            //    6825
        $version = $product->get_meta( '_mc_xii_version' );                                                                          //    6826
        if ( $continue ) {                                                                                                           //    6829
        }                                                                                                                            //    6831
        if ( ! $continue ) {                                                                                                         //    6832
            $version = $version ? $version + 1 : 1;                                                                                  //    6833
            update_post_meta( $post_id, '_mc_xii_version', $version );                                                               //    6834
            update_post_meta( $post_id, '_mc_xii_doing', 'canonicalize_variation_attributes' );                                      //    6835
        }                                                                                                                            //    6837
        $attribute_values = wc_list_pluck( array_filter( $product->get_attributes(), 'wc_attributes_array_filter_variation' ),       //    6838
                                           'get_slugs' );                                                                            //    6839
        $attribute_keys   = array_keys( $attribute_values );                                                                         //    6840
        # must directly access database data to handle variations with attributes that were changed from not optional to optional    //    6841
        # or vice versa since WC_Product_Variation objects will ignore invalid attributes on construction - see                      //    6842
        # wc_get_product_variation_attributes()                                                                                      //    6843
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                        //    6844
SELECT m.post_id, m.meta_key, m.meta_value FROM $wpdb->postmeta m, $wpdb->posts p                                                    //    6845
    WHERE m.post_id = p.ID AND p.post_parent = %d AND p.post_type = 'product_variation' AND m.meta_key LIKE 'attribute_%%'           //    6846
    ORDER BY m.post_id                                                                                                               //    6847
EOD                                                                                                                                  //    6848
            , $post_id ) );                                                                                                          //    6849
        $database_variation_attributes = [];                                                                                         //    6850
        foreach ( $results as $result ) {                                                                                            //    6851
            # check if attribute changed optional property                                                                           //    6852
            $attribute = substr( $result->meta_key, 10 );                                                                            //    6853
            if ( ! $continue ) {                                                                                                     //    6854
                if ( ! in_array( $attribute, $attribute_keys ) ) {                                                                   //    6855
                    if ( MC_Simple_Variation_Functions::is_optional_attribute_obsolete( $attribute ) ) {                             //    6856
                        $new_attribute = MC_Simple_Variation_Functions::remove_optional_suffix( $attribute );                        //    6857
                    } else {                                                                                                         //    6858
                        $new_attribute = $attribute . MC_Simple_Variation_Functions::OPTIONAL;                                       //    6859
                    }                                                                                                                //    6860
                    if ( in_array( $new_attribute, $attribute_keys ) ) {                                                             //    6861
                        delete_post_meta( $result->post_id, $result->meta_key );                                                     //    6862
                        # save attributes as they exists in the database since the API will sanitize the attributes                  //    6863
                        update_post_meta( $result->post_id, 'attribute_' . $new_attribute, $result->meta_value );                    //    6864
                        $attribute = $new_attribute;                                                                                 //    6865
                    }                                                                                                                //    6866
                }                                                                                                                    //    6867
            }                                                                                                                        //    6868
            if ( ! array_key_exists( $result->post_id, $database_variation_attributes ) ) {                                          //    6869
                $database_variation_attributes[ $result->post_id ] = [];                                                             //    6870
            }                                                                                                                        //    6871
            $database_variation_attributes[ $result->post_id ][ $attribute ] = $result->meta_value;                                  //    6872
        }                                                                                                                            //    6873
        $variation_ids = MC_Simple_Variation_Functions::get_children( $product, TRUE );                                              //    6874
        foreach ( $variation_ids as $variation_id ) {                                                                                //    6875
            if ( get_post_meta( $variation_id, '_mc_xii_version', TRUE ) == $version ) {                                             //    6876
                continue;                                                                                                            //    6877
            }                                                                                                                        //    6878
            if ( MC_Execution_Time::near_max_execution_limit() ) {                                                                   //    6879
                return FALSE;                                                                                                        //    6880
            }                                                                                                                        //    6881
            $variation            = new WC_Product_Variation( $variation_id );                                                       //    6882
            $variation_attributes = $variation->get_attributes();                                                                    //    6883
            $total_components     = 0;                                                                                               //    6884
            foreach ( $variation_attributes as $attribute => $value ) {                                                              //    6885
                if ( ! $value ) {                                                                                                    //    6886
                    $value = MC_Simple_Variation_Functions::UNSELECTED;                                                              //    6887
                }                                                                                                                    //    6888
                if ( ! in_array( $attribute, $attribute_keys ) ) {                                                                   //    6889
                    # This variation has an attribute that does not exists in its parent variable product.                           //    6890
                    # This should not be possible because when a variation is read from the database                                 //    6891
                    # wc_get_product_variation_attributes() is called to sanitize the attributes.                                    //    6892
                    error_log( 'ERROR: MC_Simple_Variation_Functions::canonicalize_variations(): Invalid attribute' );               //    6893
                    $variation->delete( TRUE );                                                                                      //    6894
                    continue 2;                                                                                                      //    6896
                }                                                                                                                    //    6897
                if ( ! in_array( $value, $attribute_values[ $attribute ] ) ) {                                                       //    6898
                    # This variation has a non existent attribute value.                                                             //    6899
                    $variation->delete( TRUE );                                                                                      //    6901
                    continue 2;                                                                                                      //    6902
                }                                                                                                                    //    6903
                if ( $value !== MC_Simple_Variation_Functions::UNSELECTED ) {                                                        //    6904
                    ++$total_components;                                                                                             //    6905
                }                                                                                                                    //    6906
            }   # foreach ( $variation_attributes as $attribute => $value ) {                                                        //    6907
            # handle variations that use a non existent component group in the database but the component is hidden by the API       //    6908
            foreach ( $database_variation_attributes[ $variation_id ] as $database_variation_attribute                               //    6909
                    => $database_variation_value ) {                                                                                 //    6910
                if ( ! in_array( $database_variation_attribute, $attribute_keys ) && $database_variation_value                       //    6911
                        !== MC_Simple_Variation_Functions::UNSELECTED ) {                                                            //    6912
                    $variation->delete( TRUE );                                                                                      //    6913
                    continue 2;                                                                                                      //    6915
               }                                                                                                                     //    6916
            }                                                                                                                        //    6917
            $variation_attribute_keys   = array_keys( $variation_attributes );                                                       //    6918
            $attribute_added            = FALSE;                                                                                     //    6919
            $missing_required_component = FALSE;                                                                                     //    6920
            foreach ( $attribute_keys as $attribute ) {                                                                              //    6921
                if ( empty( $variation_attributes[ $attribute ] ) ) {                                                                //    6922
                    $variation_attributes[ $attribute ] = MC_Simple_Variation_Functions::UNSELECTED;                                 //    6923
                    $attribute_added = TRUE;                                                                                         //    6924
                    if ( ! MC_Simple_Variation_Functions::is_optional_attribute( $attribute, $product->get_id(), $product ) ) {      //    6926
                        $missing_required_component = TRUE;                                                                          //    6927
                    }                                                                                                                //    6928
                }                                                                                                                    //    6929
            }                                                                                                                        //    6930
            if ( $missing_required_component && $total_components > 1 ) {                                                            //    6931
                # compound variation with missing required component                                                                 //    6932
                $variation->delete( TRUE );                                                                                          //    6933
                continue;                                                                                                            //    6935
            }                                                                                                                        //    6936
            if ( $attribute_added ) {                                                                                                //    6937
                $variation->set_attributes( $variation_attributes );                                                                 //    6938
                $variation->save();                                                                                                  //    6939
            }                                                                                                                        //    6941
            $attribute_keys_list = '"' . implode( '", "', array_map( function( $k ) {                                                //    6942
                return esc_sql( 'attribute_' . $k );                                                                                 //    6943
            }, $attribute_keys ) ) . '"';                                                                                            //    6944
            if ( $wpdb->get_var( $wpdb->prepare( <<<EOD                                                                              //    6945
SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( $attribute_keys_list )                 //    6946
    AND post_id = %d AND meta_value != %s                                                                                            //    6947
EOD                                                                                                                                  //    6948
                , $variation_id, MC_Simple_Variation_Functions::UNSELECTED ) ) ) {                                                   //    6949
                # This variation is invalid since it has an attribute that does not exists in its parent variable product.           //    6950
                # Classic Commerce only deletes the attribute using WC_Product_Variation_Data_Store_CPT::update_attributes().        //    6951
                # I think this is wrong because this can result in multiple variations with exactly the same attributes.             //    6952
                # I think the variation itself must be deleted.                                                                      //    6953
                $variation->delete( TRUE );                                                                                          //    6954
                continue;                                                                                                            //    6956
            }                                                                                                                        //    6957
            # below extracted from WC_Product_Variation_Data_Store_CPT::update_attributes() to remove non existent attributes        //    6958
            $delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                          //    6959
SELECT meta_key FROM $wpdb->postmeta WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( $attribute_keys_list )                 //    6960
                                                                        AND post_id = %d                                             //    6961
EOD                                                                                                                                  //    6962
                                                                     , $variation_id ) );                                            //    6963
            foreach ( $delete_attribute_keys as $key ) {                                                                             //    6964
                delete_post_meta( $variation_id, $key );                                                                             //    6965
            }                                                                                                                        //    6966
            update_post_meta( $variation->get_id(), '_mc_xii_version', $version );                                                   //    6967
        }   # foreach ( $variation_ids as $variation_id ) {                                                                          //    6969
        update_post_meta( $post_id, '_mc_xii_doing', 'waiting_for_sync' );                                                           //    6970
        update_post_meta( $post_id, '_mc_xii_updated_version', $version );                                                           //    6971
        return TRUE;                                                                                                                 //    6973
    }   # private static function canonicalize_variations( $product ) {                                                              //    6974
                                                                                                                                     //    6975
}   # class MC_Product_Variation_Data_Store_CPT extends WC_Product_Variation_Data_Store_CPT {                                        //    6976
                                                                                                                                     //    6977
MC_Product_Variation_Data_Store_CPT::init();                                                                                         //    6978
                                                                                                                                     //    6979
# MC_AJAX adds our own ajax events which will still be handled by WC_AJAX::do_wc_ajax(). Because WC_AJAX::add_variation(),           //    6980
# WC_AJAX::link_all_variations() and WC_AJAX::save_variations() instantiates a variation with "new WC_Product_Variation" and will    //    6981
# not use our MC_Product_Variation.                                                                                                  //    6982
                                                                                                                                     //    6983
class MC_AJAX {                                                                                                                      //    6984
                                                                                                                                     //    6985
    public static function init() {                                                                                                  //    6986
        add_action( 'init', 'MC_AJAX::add_ajax_events' );                                                                            //    6987
    }                                                                                                                                //    6988
                                                                                                                                     //    6989
    # add_ajax_events() must run after WC_AJAX::add_ajax_events().                                                                   //    6990
                                                                                                                                     //    6991
    public static function add_ajax_events() {                                                                                       //    6992
                                                                                                                                     //    6993
        $ajax_events = [                                                                                                             //    6994
            'save_attributes'     => FALSE,                                                                                          //    6995
            'load_variations'     => FALSE,                                                                                          //    6996

### REDACTED lines 6997 -> 7001 redacted,      5 lines redacted. ###

        ];                                                                                                                           //    7002
                                                                                                                                     //    7003
        $my_ajax_events = [                                                                                                          //    7004
            'sv_load_variations_json' => TRUE,                                                                                       //    7005
        ];                                                                                                                           //    7006
                                                                                                                                     //    7007
        # Replace some WC_AJAX events handlers - remove_action() then add_action()                                                   //    7008
                                                                                                                                     //    7009
        foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                         //    7010
            # action [ 'WC_AJAX', $ajax_event ] has already been added since that is done when the Classic Commerce plugin           //    7011
            # is loaded so safe to remove on action 'init'                                                                           //    7012
            if ( ! remove_action( 'wp_ajax_woocommerce_' . $ajax_event, [ 'WC_AJAX', $ajax_event ] ) ) {                             //    7013
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //    7014
                                   "ERROR: MC_AJAX::add_ajax_events():replacement hook for \"wp_ajax_woocommerce_$ajax_event\" not " //    7015
                                       . "installed, original hook is: " . print_r( [ 'WC_AJAX', $ajax_event ], TRUE ),              //    7016
                                   'SV 0.1.0' );                                                                                     //    7017
                wc_doing_it_wrong( __FUNCTION__,                                                                                     //    7018
                                   'ERROR: MC_AJAX::add_ajax_events():must be called after the hook is installed.', 'SV 0.1.0' );    //    7019
                break;                                                                                                               //    7020
            }                                                                                                                        //    7021
            add_action(    'wp_ajax_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                                       //    7022
            if ( $nopriv ) {                                                                                                         //    7023
                remove_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, [ 'WC_AJAX', $ajax_event ] );                            //    7024
                add_action(    'wp_ajax_nopriv_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                            //    7025
                remove_action( 'wc_ajax_' . $ajax_event, [ 'WC_AJAX', $ajax_event ] );                                               //    7026
                add_action(    'wc_ajax_' . $ajax_event, [ __CLASS__, $ajax_event ] );                                               //    7027
            }                                                                                                                        //    7028
        }   # foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                   //    7029
                                                                                                                                     //    7030
        # Add my AJAX handlers events                                                                                                //    7031
                                                                                                                                     //    7032
        foreach ( $my_ajax_events as $ajax_event => $nopriv ) {                                                                      //    7033
            add_action(    'wp_ajax_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                                       //    7034
            if ( $nopriv ) {                                                                                                         //    7035
                add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, [ __CLASS__, $ajax_event ] );                               //    7036
                add_action( 'wc_ajax_' . $ajax_event,                    [ __CLASS__, $ajax_event ] );                               //    7037
            }                                                                                                                        //    7038
        }   # foreach ( $my_ajax_events as $ajax_event => $nopriv ) {                                                                //    7039
                                                                                                                                     //    7040
        # The WC_AJAX handlers unfortunately call wp_die() so they will never return. Instead of replacing the WC_AJAX handlers the  //    7041
        # following wraps the WC_AJAX handlers and postpones the call to wp_die() so the result of the AJAX handlers can be post     //    7042
        # processed.                                                                                                                 //    7043
                                                                                                                                     //    7044
        $ajax_events = [                                                                                                             //    7045
            'save_variations' => FALSE                                                                                               //    7046
        ];                                                                                                                           //    7047
                                                                                                                                     //    7048
        foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                         //    7049
            MC_Hook_Wrapper::wrap_hook( "WC_AJAX::{$ajax_event}", [ __CLASS__, $ajax_event ],                                        //    7050
                    'wp_ajax_woocommerce_' . $ajax_event, [ 'WC_AJAX', $ajax_event ], TRUE );                                        //    7051
        }   # foreach ( $ajax_events as $ajax_event => $nopriv ) {                                                                   //    7052
                                                                                                                                     //    7053
    }   # public static function add_ajax_events() {                                                                                 //    7054
                                                                                                                                     //    7055
                                                                                                                                     //    7056
    public static function save_attributes() {                                                                                       //    7058
        if ( ! MC_Simple_Variation_Functions::is_simple_variable( absint( $_POST['post_id'] ) )                                      //    7059
                && strpos( $_POST['data'], '&attribute_for_simple_variation%5B' ) === FALSE ) {                                      //    7060
            return WC_AJAX::save_attributes();                                                                                       //    7061
        }                                                                                                                            //    7062
        # Do attributes of a Simple Variable Product                                                                                 //    7063
        self::sv_save_attributes();                                                                                                  //    7064
    }   # public static function save_attributes() {                                                                                 //    7065
                                                                                                                                     //    7066
    public static function load_variations() {                                                                                       //    7068
        if ( ! MC_Simple_Variation_Functions::is_simple_variable( $_POST['product_id'] ) ) {                                         //    7069
            return WC_AJAX::load_variations();                                                                                       //    7070
        }                                                                                                                            //    7071
        # Do variations of a Simple Variable Product                                                                                 //    7072
        self::sv_load_variations();                                                                                                  //    7073
    }   # public static function load_variations() {                                                                                 //    7074
                                                                                                                                     //    7075
    private static function sv_save_attributes() {                                                                                   //    7076
        # The response to this AJAX action 'woocommerce_save_attributes' will trigger an AJAX action                                 //    7078
        # 'woocommerce_load_variations' which will call self::sv_load_variations()                                                   //    7079
        if ( MC_Simple_Variation_Functions::is_simple_variable( ( $post_id = absint( $_POST['post_id'] ) ) )                         //    7080
                || strpos( $_POST['data'], '&attribute_for_simple_variation%5B' ) !== FALSE ) {                                      //    7081
            parse_str( $_POST['data'], $data );                                                                                      //    7082
            MC_Simple_Variation_Functions::prepare_request_product_attributes( $data, $post_id );                                    //    7084
            $_POST['data'] = http_build_query( $data );                                                                              //    7085
            # Return an empty function so die() is prevented.                                                                        //    7086
            # add_filter( 'wp_die_ajax_handler', function( $die_handler ) {                                                          //    7087
            #     return function( $message, $title, $args ) {};                                                                     //    7088
            # } );                                                                                                                   //    7089
            $die_call = &MC_Utility::postpone_wp_die();                                                                              //    7090
            # WC_AJAX::save_attributes() will return since die() will not be called because of the 'wp_die_ajax_handler' filter.     //    7091
            WC_AJAX::save_attributes();                                                                                              //    7092
            $product    = wc_get_product( $post_id );                                                                                //    7093
            $data_store = WC_Data_Store::load( 'product-variation' );                                                                //    7094
            $data_store->canonicalize_variations( $product );                                                                        //    7095
            if ( MC_Product_Data_Store_CPT::is_virtual_variable( $post_id )                                                          //    7096
                    && ! MC_Product_Data_Store_CPT::check_count_of_virtual_variations( $post_id, $count ) ) {                        //    7097
            }                                                                                                                        //    7098
            # Must call _ajax_wp_die_handler() directly since we have installed a 'wp_die_ajax_handler' filter.                      //    7099
            # _ajax_wp_die_handler( '', '', [ 'response' => NULL ] );                                                                //    7100
            MC_Utility::do_postponed_wp_die( $die_call );                                                                            //    7101
        }                                                                                                                            //    7102
    }   # private static function sv_save_attributes() {                                                                             //    7103
                                                                                                                                     //    7104
    # sv_load_variations() loads the base variations of a simple variable product. It replaces the Classic Commerce                  //    7105
    # load_variations() for simple variable products.                                                                                //    7106
    private static function sv_load_variations( $ajax = TRUE, $product_id = NULL ) {                                                 //    7107
        global $post;                                                                                                                //    7115
        global $wpdb;                                                                                                                //    7116
                                                                                                                                     //    7117
        # Check permissions again and make sure we have what we need                                                                 //    7118
        if ( ! current_user_can( 'edit_products' ) ) {                                                                               //    7119
            die( -1 );                                                                                                               //    7120
        }                                                                                                                            //    7121
        if ( $ajax ) {                                                                                                               //    7122
            check_ajax_referer( 'load-variations', 'security' );                                                                     //    7123
            if ( empty( $_POST['product_id'] ) ) {                                                                                   //    7124
                die( -1 );                                                                                                           //    7125
            }                                                                                                                        //    7126
        } else {                                                                                                                     //    7127
            ob_start();                                                                                                              //    7128
        }                                                                                                                            //    7129
        $product_id   = $ajax ? absint( $_POST['product_id'] ) : absint( $product_id );                                              //    7130
        if ( MC_Simple_Variation_Functions::simple_variable_is_synchronizing( $product_id ) ) {                                      //    7131
?>                                                                                                                                   <!--  7132 -->
<div class="toolbar toolbar-top toolbar-simple_variation" style="background-color:#fff">                                             <!--  7133 -->
Components panel is not available until synchronization has completed.                                                               <!--  7134 -->
</div>                                                                                                                               <!--  7135 -->
<?php                                                                                                                                //    7136
            if ( ! $ajax ) {                                                                                                         //    7137
                $contents = ob_get_contents();                                                                                       //    7138
                ob_end_clean();                                                                                                      //    7139
                return $contents;                                                                                                    //    7140
            }                                                                                                                        //    7141
            die();                                                                                                                   //    7142
        }                                                                                                                            //    7143
        $loop         = '';                                                                                                          //    7144
        $variation_id = '';                                                                                                          //    7145
        $post         = get_post( $product_id );   # Set $post global so its available like within the admin screens                 //    7146
        $product      = wc_get_product( $product_id );                                                                               //    7147
?>                                                                                                                                   <!--  7148 -->
<div class="toolbar toolbar-top toolbar-simple_variation" style="background-color:#fff">                                             <!--  7149 -->
    &nbsp;                                                                                                                           <!--  7150 -->
<?php                                                                                                                                //    7151
        if ( PHP_INT_MAX >= 9223372036854775807 ) {                                                                                  //    7152
?>                                                                                                                                   <!--  7153 -->
    <input type="checkbox" name="mc_xii_virtual_variations"                                                                          <!--  7154 -->
        <?php echo MC_Product_Data_Store_CPT::is_virtual_variable( $product_id ) ? ' checked' : ''; ?>>                              <!--  7155 -->
    Use virtual composite products                                                                                                   <!--  7156 -->
    <input type="hidden" name="mc_xii_virtual_variations_1"                                                                          <!--  7157 -->
        value="<?php echo MC_Product_Data_Store_CPT::is_virtual_variable( $product_id ) ? 'on' : 'off'; ?>">                         <!--  7158 -->
<?php                                                                                                                                //    7159
        }                                                                                                                            //    7160
?>                                                                                                                                   <!--  7161 -->
    <div class="variations-pagenav">                                                                                                 <!--  7162 -->
        <span class="expand-close">                                                                                                  <!--  7163 -->
           (<a href="#" class="expand_all"><?php echo ucfirst( MC_Simple_Variation_Functions::$expand_label ); ?></a> /              <!--  7164 -->
            <a href="#" class="close_all" ><?php echo ucfirst( MC_Simple_Variation_Functions::$close_label );  ?></a>)               <!--  7165 -->
        </span>                                                                                                                      <!--  7166 -->
    </div>                                                                                                                           <!--  7167 -->
</div>                                                                                                                               <!--  7168 -->
<?php                                                                                                                                //    7169
        # $data_store = WC_Data_Store::load( 'product-variable' ); This does not work as load() returns a wrapper for                //    7170
        # the MC_Product_Variable_Data_Store_CPT object and get_base_variations_for_attributes() is resolved by                      //    7171
        # WC_Data_Store::__call() which cannot handle parameters passed by reference. The second, third and fourth parameters to     //    7172
        # get_base_variations_for_attributes() should be passed by reference.                                                        //    7173
        $data_store                   = new MC_Product_Variable_Data_Store_CPT();                                                    //    7174
        $base_variation_for_attribute = $data_store->get_base_variations_for_attributes( $product->get_id(), $attributes,            //    7175
                                            $attribute_values, $map_attributes_to_variation );                                       //    7176
                                                                                                                                     //    7177
        # below extracted from WC_Meta_Box_Product_Data::output_variations( )                                                        //    7178
                                                                                                                                     //    7179
        $variations_count       = absint( $wpdb->get_var( $wpdb->prepare( <<<EOD                                                     //    7180
SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation'                                        //    7181
    AND post_status IN ( 'publish', 'private' )                                                                                      //    7182
EOD                                                                                                                                  //    7183
                                          , $post->ID ) ) );                                                                         //    7184
        $variations_per_page    = absint( apply_filters( 'woocommerce_admin_meta_boxes_variations_per_page', 15 ) );                 //    7185
        $variations_total_pages = ceil( $variations_count / $variations_per_page );                                                  //    7186
        $data_store             = $product->get_data_store();                                                                        //    7187
        $variation_counts       = $data_store->get_variation_counts( $product->get_id() );                                           //    7188
?>                                                                                                                                   <!--  7189 -->
<div class="toolbar mc_xii-simple_variations_top">                                                                                   <!--  7190 -->
<?php                                                                                                                                //    7191
        foreach ( $variation_counts['counts_by_attributes'] as $attribute => $count ) {                                              //    7192
            if ( in_array( $attribute, $variation_counts['optional'] ) ) {                                                           //    7193
                printf( MC_Simple_Variation_Functions::$there_are_variations_for_optional, $count, $attribute );                     //    7194
            } else {                                                                                                                 //    7195
                printf( MC_Simple_Variation_Functions::$there_are_variations_for_component, $count, $attribute );                    //    7196
            }                                                                                                                        //    7197
        }                                                                                                                            //    7198
        printf( MC_Simple_Variation_Functions::$from_these_base_will_be_generated,                                                   //    7199
                $variation_counts[ MC_Simple_Variation_Functions::TYPE_BASE ],                                                       //    7200
                $variation_counts[ MC_Simple_Variation_Functions::TYPE_COMPOUND ] );                                                 //    7201
        if ( ! MC_Product_Data_Store_CPT::is_virtual_variable( $product_id ) ) {                                                     //    7202
            if  ( $cumulative = get_post_meta( $product_id, '_mc_xii_prev_cumulative_elapsed_time', TRUE ) ) {                       //    7203
                printf( MC_Simple_Variation_Functions::$the_last_complete_update_used, number_format( $cumulative[0], 2),            //    7204
                        $cumulative[1], $cumulative[2], $cumulative[3] );                                                            //    7205
            }                                                                                                                        //    7206
        }                                                                                                                            //    7207
?>                                                                                                                                   <!--  7208 -->
</div>                                                                                                                               <!--  7209 -->
<?php                                                                                                                                //    7210
        # do the base variations grouped by their attribute                                                                          //    7211
        $loop           = 0;                                                                                                         //    7212
        $product_object = wc_get_product( $product_id );                                                                             //    7213
        foreach ( $attributes as $slug => $the_attribute ) {                                                                         //    7215
            $attribute_name = $the_attribute[ 'name' ];                                                                              //    7216
            if ( MC_Simple_Variation_Functions::is_optional_attribute( $slug, $product_id, $product_object ) ) {                     //    7217
                $attribute_name = MC_Simple_Variation_Functions::remove_optional_suffix( $attribute_name );                          //    7218
            }                                                                                                                        //    7219
            $variations_for_attribute = $base_variation_for_attribute[ $slug ];                                                      //    7220
?>                                                                                                                                   <!--  7221 -->
<div class="woocommerce_variation wc-metabox closed">                                                                                <!--  7222 -->
    <h3>                                                                                                                             <!--  7223 -->
        <div class="handlediv" title="<?php echo esc_attr( ucfirst( MC_Simple_Variation_Functions::$click_to_toggle ) ); ?>"></div>  <!--  7224 -->
        <?php echo '<strong>' . $attribute_name . '</strong>'; ?>                                                                    <!--  7225 -->
    </h3>                                                                                                                            <!--  7226 -->
    <div class="woocommerce_variable_attributes wc-metabox-content" style="display: none;">                                          <!--  7227 -->
        <div class="data" style="background-color:#f1f1f1;">                                                                         <!--  7228 -->
<?php                                                                                                                                //    7229
            # below extracted from html-variation-admin.php                                                                          //    7230
            $options = wc_get_text_attributes( $the_attribute['value'] );                                                            //    7231
            foreach ( $options as $option ) {                                                                                        //    7232
                if ( $option === MC_Simple_Variation_Functions::UNSELECTED ) {                                                       //    7233
                    continue;                                                                                                        //    7234
                }                                                                                                                    //    7235
                ++$loop;                                                                                                             //    7236
                $variation    = $variations_for_attribute[ $option ];                                                                //    7237
                $variation_id = $variation instanceof WC_Product_Variation ? $variation->get_id() : $variation['id'];                //    7238
                self::load_variations_pane_header( $loop, $variation_id, $attributes, $the_attribute, $option );                     //    7239
                $variation_object = $variation instanceof WC_Product_Variation ? $variation                                          //    7240
                                                                               : new WC_Product_Variation( $variation['id'] );       //    7241
                $variation_id     = $variation_object->get_id();                                                                     //    7242
                $variation        = get_post( $variation_id );                                                                       //    7243
                $variation_data   = array_merge( array_map( 'maybe_unserialize', get_post_custom( $variation_id ) ),                 //    7244
                                                 wc_get_product_variation_attributes( $variation_id ) );   # kept for BW compat.     //    7245
                ob_start( function( $buffer ) {                                                                                      //    7246
                    $buffer = preg_replace( [ '#^.+?</h3>#s', '#</div>\s*$#' ], '', $buffer );                                       //    7247
                    return $buffer;                                                                                                  //    7248
                });                                                                                                                  //    7249
                include( plugin_dir_path( __DIR__ ) . MC_Simple_Variation_Functions::$classic_commerce                               //    7250
                         . '/includes/admin/meta-boxes/views/html-variation-admin.php' );                                            //    7251
                ob_end_flush();                                                                                                      //    7252
?>                                                                                                                                   <!--  7253 -->
</div>                                                                                                                               <!--  7254 -->
<?php                                                                                                                                //    7255
            }   # foreach ( $options as $option ) {                                                                                  //    7256
            # above extracted from html-variation-admin.php                                                                          //    7257
?>                                                                                                                                   <!--  7258 -->
        </div>                                                                                                                       <!--  7259 -->
    </div>                                                                                                                           <!--  7260 -->
</div>                                                                                                                               <!--  7261 -->
<?php                                                                                                                                //    7262
        }   # foreach ( $attributes as $slug => $the_attribute ) {                                                                   //    7263
                                                                                                                                     //    7264
        # above extracted from WC_Meta_Box_Product_Data::output_variations()                                                         //    7265
                                                                                                                                     //    7266
        if ( ! $ajax ) {                                                                                                             //    7267
            $contents = ob_get_contents();                                                                                           //    7268
            ob_end_clean();                                                                                                          //    7269
            return $contents;                                                                                                        //    7270
        }                                                                                                                            //    7271
                                                                                                                                     //    7272
        die();                                                                                                                       //    7273
    }   # public static function sv_load_variations( $ajax = TRUE, $product_id = NULL ) {                                            //    7274
                                                                                                                                     //    7275
    # load_variations_pane_header() is a helper function for sv_load_variations()                                                    //    7276
                                                                                                                                     //    7277
    private static function load_variations_pane_header( $loop, $variation_id, $attributes, $attribute, $option ) {                  //    7278
?>                                                                                                                                   <!--  7279 -->
<div class="woocommerce_variation wc-metabox closed">                                                                                <!--  7280 -->
  <h3>                                                                                                                               <!--  7281 -->
<?php                                                                                                                                //    7282
        foreach ( $attributes as $alt_attribute ) {                                                                                  //    7283
            echo '<input type="hidden" name="attribute_' . sanitize_title( $alt_attribute['name'] ) . '[' . $loop . ']" value="'     //    7284
                    . ( $alt_attribute[ 'name' ] === $attribute[ 'name' ] ? esc_attr( $option )                                      //    7285
                                                                          : MC_Simple_Variation_Functions::UNSELECTED ) . '">';      //    7286
        }                                                                                                                            //    7287
?>                                                                                                                                   <!--  7288 -->
    <div class="handlediv" title="<?php echo esc_attr( ucfirst( MC_Simple_Variation_Functions::$click_to_toggle ) ); ?>"></div>      <!--  7289 -->
    <input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />           <!--  7290 -->
    <!-- below is not used by simple variations but necessary to prevent undefined index errors in                                   <!--  7291 -->
        classic-commerce\includes\admin\meta-boxes\class-wc-meta-box-product-data.php -->                                            <!--  7292 -->
    <input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="" />                  <!--  7293 -->
<?php                                                                                                                                //    7294
        echo '<strong>#' . esc_html( $variation_id ) . '</strong>&nbsp;&nbsp;';                                                      //    7295
        echo '<strong>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</strong>';                   //    7296
?>                                                                                                                                   <!--  7297 -->
  </h3>                                                                                                                              <!--  7298 -->
<?php                                                                                                                                //    7299
    }   # private static function load_variations_pane_header( $loop, $variation_id, $attributes, $attribute, $option ) {            //    7300
                                                                                                                                     //    7301
    # sv_load_variations_json() services requests from React Redux middleware.                                                       //    7302
                                                                                                                                     //    7303
    public static function sv_load_variations_json() {                                                                               //    7305
        global $wpdb;                                                                                                                //    7306
        if ( empty( $_REQUEST['product_id'] ) || ! wc_get_product( absint( $_REQUEST['product_id'] ) ) ) {                           //    7307
            wp_die();                                                                                                                //    7308
        }                                                                                                                            //    7309
        $wpdb->query( 'SET SQL_BIG_SELECTS=1' );                                                                                     //    7310
        $product_id    = absint( $_REQUEST[ 'product_id' ] );                                                                        //    7311
        $title         = $wpdb->get_col( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d", $product_id ) );       //    7312
        $variation_ids = $wpdb->get_col( $wpdb->prepare( <<<EOD                                                                      //    7313
SELECT ID FROM $wpdb->posts p, $wpdb->postmeta m WHERE p.ID = m.post_id                                                              //    7314
    AND p.post_parent = %d AND p.post_status = 'publish' AND m.meta_key = '_mc_xii_variation_type' AND m.meta_value = 'base'         //    7315
EOD                                                                                                                                  //    7316
                                                             , $product_id ) );                                                      //    7317
        $variation_ids = implode( ', ', $variation_ids );                                                                            //    7318
        $unselected    = MC_Simple_Variation_Functions::UNSELECTED;                                                                  //    7319
        $query = <<<EOD                                                                                                              //    7320
SELECT m_attribute.post_id id, m_attribute.meta_key attribute, m_attribute.meta_value selection, m_price.meta_value price,           //    7321
        m_quantity.meta_value quantity, m_image.meta_value image_id, m_description.meta_value description                            //    7322
    FROM $wpdb->postmeta m_attribute, $wpdb->postmeta m_price, $wpdb->postmeta m_quantity,                                           //    7323
            $wpdb->postmeta m_image, $wpdb->postmeta m_description                                                                   //    7324
    WHERE m_attribute.post_id in ( $variation_ids )                                                                                  //    7325
        AND m_price.post_id        = m_attribute.post_id                                                                             //    7326
        AND m_quantity.post_id     = m_attribute.post_id                                                                             //    7327
        AND m_image.post_id        = m_attribute.post_id                                                                             //    7328
        AND m_description.post_id  = m_attribute.post_id                                                                             //    7329
        AND m_attribute.meta_key LIKE 'attribute\\_%' AND m_attribute.meta_value != '$unselected'                                    //    7330
        AND m_price.meta_key       = '_price'                                                                                        //    7331
        AND m_quantity.meta_key    = '_stock'                                                                                        //    7332
        AND m_image.meta_key       = '_thumbnail_id'                                                                                 //    7333
        AND m_description.meta_key = '_variation_description'                                                                        //    7334
EOD;                                                                                                                                 //    7335
        $results = $wpdb->get_results( $query, OBJECT_K );                                                                           //    7337
        if ( empty( $results ) ) {                                                                                                   //    7339
            if ( $wpdb->use_mysqli ) {                                                                                               //    7340
                $error = mysqli_error( $wpdb->dbh );                                                                                 //    7341
            } else {                                                                                                                 //    7342
                $error = mysql_error( $wbdb->dbh );                                                                                  //    7343
            }                                                                                                                        //    7344
            # wp_send_json( $error );                                                                                                //    7347
            # $error is:                                                                                                             //    7348
            # The SELECT would examine more than MAX_JOIN_SIZE rows;                                                                 //    7349
            # check your WHERE and use SET SQL_BIG_SELECTS=1 or SET MAX_JOIN_SIZE=# if the SELECT is okay                            //    7350
            # $max_join_size = $wpdb->get_results( 'SHOW VARIABLES LIKE "MAX_JOIN_SIZE"' );                                          //    7351
            # wp_send_json( $max_join_size );                                                                                        //    7353
            # but $max_join_size is:                                                                                                 //    7354
            # [{"Variable_name":"max_join_size","Value":"4000000"}]                                                                  //    7355
            # $sql_big_selects = $wpdb->get_results( 'SHOW VARIABLES LIKE "SQL_BIG_SELECTS"' );                                      //    7356
            # wp_send_json( $sql_big_selects );                                                                                      //    7358
            # sql_big_selects is:                                                                                                    //    7359
            # [{"Variable_name":"sql_big_selects","Value":"OFF"}]                                                                    //    7360
            # $wpdb->query( 'SET SQL_BIG_SELECTS=1' );                                                                               //    7361
            # $sql_big_selects = $wpdb->get_results( 'SHOW VARIABLES LIKE "SQL_BIG_SELECTS"' );                                      //    7362
            # wp_send_json( $sql_big_selects );                                                                                      //    7364
            # now sql_big_selects is:                                                                                                //    7365
            # [{"Variable_name":"sql_big_selects","Value":"ON"}]                                                                     //    7366
            # But the first $wpdb->get_results() still fails!                                                                        //    7367
            $results = $wpdb->get_results( <<<EOD                                                                                    //    7368
SELECT post_id id, meta_key attribute, meta_value selection FROM $wpdb->postmeta                                                     //    7369
    WHERE post_id in ( $variation_ids ) AND meta_key LIKE 'attribute\\_%' AND meta_value != '$unselected'                            //    7370
EOD                                                                                                                                  //    7371
                                           , OBJECT_K );                                                                             //    7372
            $fields = [                                                                                                              //    7373
                (object) [ 'key' => '_price',                 'name' => 'price'    ],                                                //    7374
                (object) [ 'key' => '_stock',                 'name' => 'quantity' ],                                                //    7375
                (object) [ 'key' => '_thumbnail_id',          'name' => 'image_id' ],                                                //    7376
                (object) [ 'key' => '_variation_description', 'name' => 'description' ]                                              //    7377
            ];                                                                                                                       //    7378
            $field_results = self::join_post_meta( $variation_ids, $fields );                                                        //    7379
            foreach ( $field_results as $id => $field_result ) {                                                                     //    7380
                $field_result->id        = $results[ $id ]->id;                                                                      //    7381
                $field_result->attribute = $results[ $id ]->attribute;                                                               //    7382
                $field_result->selection = $results[ $id ]->selection;                                                               //    7383
            }                                                                                                                        //    7384
            $results = $field_results;                                                                                               //    7385
        }                                                                                                                            //    7387
        foreach ( $results as $id => $result ) {                                                                                     //    7388
            $result->product_name = $title[0];                                                                                       //    7389
            if ( is_array( $full_size_image = wp_get_attachment_image_src( $result->image_id, 'full' ) ) && $full_size_image ) {     //    7390
                $result->full_size_image        = $full_size_image[0];                                                               //    7391
                $result->full_size_image_width  = $full_size_image[1];                                                               //    7392
                $result->full_size_image_height = $full_size_image[2];                                                               //    7393
            }                                                                                                                        //    7394
            if ( is_array( $thumbnail = wp_get_attachment_image_src( $result->image_id, 'shop_thumbnail' ) ) && $thumbnail ) {       //    7395
                $result->thumbnail = $thumbnail[0];                                                                                  //    7396
            }                                                                                                                        //    7397
            if ( ! empty( $_REQUEST[ 'image_props' ] ) ) {                                                                           //    7398
                $result->image_props = wc_get_product_attachment_props( $result->image_id );                                         //    7399
            }                                                                                                                        //    7400
        }                                                                                                                            //    7401
        wp_send_json( $results );                                                                                                    //    7403
    }   # public static function sv_load_variations_json() {                                                                         //    7404
                                                                                                                                     //    7405
    # join_post_meta() is a helper function for sv_load_variations_json().                                                           //    7406
                                                                                                                                     //    7407
    private static function join_post_meta( $ids, $fields ) {                                                                        //    7408
        global $wpdb;                                                                                                                //    7409
        $ids = is_array( $ids ) ? implode( ', ', $ids ) : $ids;                                                                      //    7410
        $results = [];                                                                                                               //    7411
        foreach ( $fields as $field ) {                                                                                              //    7412
            $values = $wpdb->get_results( $wpdb->prepare( <<<EOD                                                                     //    7413
SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND post_id in ( $ids )                                          //    7414
EOD                                                                                                                                  //    7415
                                                        , $field->key ), OBJECT );                                                   //    7416
            foreach ( $values as $value ) {                                                                                          //    7417
                $results[ $value->post_id ][ $field->name ] = $value->meta_value;                                                    //    7418
            }                                                                                                                        //    7419
        }                                                                                                                            //    7420
        $results = array_map( function( $result ) {                                                                                  //    7421
            return (object) $result;                                                                                                 //    7422
        }, $results );                                                                                                               //    7423
        return $results;                                                                                                             //    7424
    }   # private static function join_post_meta( $ids, $fields ) {                                                                  //    7425
                                                                                                                                     //    7426

### REDACTED lines 7427 -> 7443 redacted,     17 lines redacted. ###

                                                                                                                                     //    7444
    # MC_AJAX::save_variations() is a wrapper for WC_AJAX::save_variations() installed with MC_Hook_Wrapper::wrap_hook().            //    7445
    # This is tricky as WC_AJAX::save_variations() calls wp_die() and does not return. However, using MC_Utility::postpone_wp_die()  //    7446
    # we can get WC_AJAX::save_variations() to return and then do post processing after the call.                                    //    7447
                                                                                                                                     //    7448
    public static function save_variations( $callback ) {                                                                            //    7450
        # $callback should be  WC_AJAX::save_variations()                                                                            //    7451
        check_ajax_referer( 'save-variations', 'security' );                                                                         //    7452
        if ( ! current_user_can( 'edit_products' ) || empty( $_POST ) || empty( $_POST['product_id'] ) ) {                           //    7453
            wp_die( -1 );                                                                                                            //    7454
        }                                                                                                                            //    7455
        $old_manage_stock = NULL;                                                                                                    //    7456
        $new_manage_stock = NULL;                                                                                                    //    7457
        if ( isset( $_POST['variable_post_id'] ) ) {                                                                                 //    7458
            $max_loop = max( array_keys( $_POST['variable_post_id'] ) );                                                             //    7459
            for ( $i = 0; $i <= $max_loop; $i++ ) {                                                                                  //    7460
                if ( ! isset( $_POST['variable_post_id'][ $i ] ) ) {                                                                 //    7461
                  continue;                                                                                                          //    7462
                }                                                                                                                    //    7463
                $_POST['variable_shipping_class'][$i] = '-1';                                                                        //    7464
                $_POST['variable_tax_class'][$i]      = 'parent';                                                                    //    7465
            }                                                                                                                        //    7466
            if ( isset( $_POST['variable_post_id'] ) ) {                                                                             //    7467
                $index = min( array_keys( $_POST['variable_post_id'] ) );                                                            //    7468
                $new_manage_stock = isset( $_POST['variable_manage_stock'][ $index ] );                                              //    7469
                $variation_id = absint( $_POST['variable_post_id'][ $index ] );                                                      //    7470
                $old_manage_stock = wc_string_to_bool( get_post_meta( $variation_id, '_manage_stock', TRUE ) );                      //    7471
            }                                                                                                                        //    7472
        }                                                                                                                            //    7473
        # $callback is WC_AJAX::save_variations() which normally exits by calling wp_die()                                           //    7474
        $die_call = &MC_Utility::postpone_wp_die();                                                                                  //    7475
        call_user_func( $callback );                                                                                                 //    7476
        # called via AJAX action 'wp_ajax_woocommerce_[nopriv_]save_variations'                                                      //    7477
        # called by WC_AJAX::save_variations()                                                                                       //    7478
        # WC_AJAX::save_variations() will also be called when the post is saved                                                      //    7479
        #     - see wc_meta_boxes_product_variations_ajax.save_on_submit() in meta-boxes-product-variation.js                        //    7480
        # WC_AJAX::save_variations() calls WC_Meta_Box_Product_Data::save_variations()                                               //    7481
        # WC_Meta_Box_Product_Data::save_variations() does $variation = new WC_Product_Variation(); $variation->set_props();         //    7482
        #     $variation->save();                                                                                                    //    7483
        # consider using action 'woocommerce_save_product_variation' in WC_Meta_Box_Product_Data::save_variations()                  //    7484
        global $wpdb;                                                                                                                //    7485
        $id = absint( $_POST['product_id'] );                                                                                        //    7486
        if ( self::is_simple_variable( $id ) ) {                                                                                     //    7487
            MC_Product_Data_Store_CPT::update_virtual_variable_product_attributes_version( $id );                                    //    7488
            # Update the compound variations of a Simple Variable Product                                                            //    7489
            MC_Simple_Variation_Functions::sync_compound_variations_with_base_variations( $id );                                     //    7490
            if ( isset( $_POST['variable_post_id'] ) && is_array( $_POST['variable_post_id'] )                                       //    7491
                && isset( $_POST['variable_manage_stock'][ min( array_keys( $_POST['variable_post_id'] ) ) ] ) ) {                   //    7492
                # Below is necessary as WC_Post_Data::WC_Product::deferred_product_sync() does not handle the following properties.  //    7493
                # See wc_deferred_product_sync( $this->get_parent_id() )                                                             //    7494
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = '" . wc_bool_to_string( FALSE )                               //    7495
                                  . "' WHERE post_id = $id AND meta_key = '_manage_stock'" );                                        //    7496
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = NULL WHERE post_id = $id AND meta_key = '_stock'" );          //    7497
                $wpdb->query( "UPDATE $wpdb->postmeta SET meta_value = 'instock'"                                                    //    7498
                                  . " WHERE post_id = $id AND meta_key = '_stock_status'" );                                         //    7499
            }                                                                                                                        //    7500
            if ( $new_manage_stock && ! $old_manage_stock ) {                                                                        //    7501
                $now = new WC_DateTime();                                                                                            //    7502
                update_post_meta( $id, '_mc_xii_variation_manage_stock', "$id:" . $now->getTimestamp() );                            //    7503
            } else if ( ! $new_manage_stock && $old_manage_stock ) {                                                                 //    7504
                delete_post_meta( $id, '_mc_xii_variation_manage_stock' );                                                           //    7505
            }                                                                                                                        //    7506
        }                                                                                                                            //    7507
        MC_Utility::do_postponed_wp_die( $die_call );                                                                                //    7508
    }                                                                                                                                //    7509
                                                                                                                                     //    7510
}   # class MC_AJAX {                                                                                                                //    7511
                                                                                                                                     //    7512
MC_AJAX::init();                                                                                                                     //    7513

### REDACTED lines 7514 -> 7676 redacted,    163 lines redacted. ###

                                                                                                                                     //    7677
if ( MC_Simple_Variation_Functions::$load_demo_class ) {                                                                             //    7678
                                                                                                                                     //    7679
    class MC_Simple_Variation_Demo_Functions extends MC_Simple_Variation_Functions {                                                 //    7680
                                                                                                                                     //    7681
        const SAND_BOX                            = 'mc_xii_sandbox';                                                                //    7682
                                                                                                                                     //    7683

### REDACTED lines 7684 -> 7704 redacted,     21 lines redacted. ###

                                                                                                                                     //    7705
        public static function init() {                                                                                              //    7706
            global $wpdb;                                                                                                            //    7707
                                                                                                                                     //    7708

### REDACTED lines 7709 -> 7794 redacted,     86 lines redacted. ###

                                                                                                                                     //    7795
                MC_Utility::add_filter( 'woocommerce_login_redirect', 'mc_xii_woocommerce_login_redirect',                           //    7796
                        function( $redirect_to, $user ) {                                                                            //    7797
                    return self::login_redirect( $redirect_to, $user );                                                              //    7798
                }, 10, 2 );                                                                                                          //    7799
                                                                                                                                     //    7800
                MC_Utility::add_filter( 'login_redirect', 'mc_xii_login_redirect',                                                   //    7801
                        function( $redirect_to, $requested_redirect_to, $user ) {                                                    //    7802
                    return self::login_redirect( $redirect_to, $user );                                                              //    7803
                }, 10, 3 );                                                                                                          //    7804
                                                                                                                                     //    7805

### REDACTED lines 7806 -> 7843 redacted,     38 lines redacted. ###

                                                                                                                                     //    7844
            MC_Utility::add_action( 'admin_init', 'mc_xii_admin_init_demo_functions', function() use ( $demo_user_enabled ) {        //    7845
                                                                                                                                     //    7846

### REDACTED lines 7847 -> 7868 redacted,     22 lines redacted. ###

                                                                                                                                     //    7869
                if ( $demo_user_enabled && self::is_demo_user() ) {                                                                  //    7870
                    add_filter( 'submenu_file', 'MC_Simple_Variation_Demo_Functions::fix_admin_menu', 1 );                           //    7871
                                                                                                                                     //    7872

### REDACTED lines 7873 -> 7955 redacted,     83 lines redacted. ###

                                                                                                                                     //    7956
                    MC_Utility::add_action( 'add_admin_bar_menus', 'mc_xii_add_admin_bar_menus', function() {                        //    7957
                        MC_Utility::add_action( 'admin_bar_menu', 'mc_xii_admin_bar_menu', function( $wp_admin_bar ) {               //    7958
                            $wp_admin_bar->remove_node( 'new-content' );                                                             //    7960
                        }, PHP_INT_MAX, 1 );                                                                                         //    7961
                    } );                                                                                                             //    7962
                }   # if ( $demo_user_enabled && self::is_demo_user() ) {                                                            //    7963
                # 9 because this must run before WC_Admin::prevent_admin_access() which runs at the default 10                       //    7964
            }, 9 );   # MC_Utility::add_action( 'admin_init', 'mc_xii_admin_init_demo_functions', function() use ( $demo_user_enable //    7965
                                                                                                                                     //    7966
            MC_Utility::add_action( 'init', 'mc_xii_init_demo_functions', function() use ( $demo_user_enabled ) {                    //    7967
                                                                                                                                     //    7968

### REDACTED lines 7969 -> 8009 redacted,     41 lines redacted. ###

                                                                                                                                     //    8010
                if ( ! is_admin() && $demo_user_enabled && self::is_demo_user() ) {                                                  //    8011
                    MC_Utility::add_filter( 'wp_page_menu', 'mc_xii_wp_page_menu', function( $menu ) {                               //    8012
                        static $count = 0;                                                                                           //    8013
                        # This filter called twice. Duplicate menus are emitted. Apparently, frontend code must be fixing this.      //    8017
                        # [01-Sep-2022 11:28:24 UTC] FILTER::wp_page_menu():$count = 1, crc32($menu) = 1584987908                    //    8018
                        # [01-Sep-2022 11:28:24 UTC] FILTER::wp_page_menu():$count = 2, crc32($menu) = 1584987908                    //    8019
                        # [01-Sep-2022 11:28:24 UTC] FILTER::wp_page_menu():BACKTRACE =                                              //    8020
                        # require('wp-blog-header.php')                                                                              //    8021
                        # require_once('wp-includes/template-loader.php')                                                            //    8022
                        # include('/plugins/classic-commerce/templates/single-product.php')                                          //    8023
                        # get_header                                                                                                 //    8024
                        # locate_template                                                                                            //    8025
                        # load_template                                                                                              //    8026
                        # require_once('/themes/storefront/header.php')                                                              //    8027
                        # do_action('storefront_header')                                                                             //    8028
                        # WP_Hook->do_action                                                                                         //    8029
                        # WP_Hook->apply_filters                                                                                     //    8030
                        # storefront_primary_navigation                                                                              //    8031
                        # wp_nav_menu                                                                                                //    8032
                        # wp_page_menu                                                                                               //    8033
                        # apply_filters('wp_page_menu')                                                                              //    8034
                        # WP_Hook->apply_filters                                                                                     //    8035
                        # Kwdb_::{closure}                                                                                           //    8036
                        if ( strpos( $menu, __( 'Log Out' ) ) !== FALSE ) {                                                          //    8037
                            return $menu;                                                                                            //    8038
                        }                                                                                                            //    8039
                        $menu = preg_replace_callback( '#<li.+?href="(.+?)".*?>(.+?)</a></li>#s', function( $matches ) {             //    8041
                            if ( $matches[ 2 ] === __( 'Home', 'classic-commerce' ) ) {                                              //    8042
                                return $matches[ 0 ];                                                                                //    8043
                            }                                                                                                        //    8044
                            $includes = [ '/cart/', '/checkout/', '/my-account/', '/shop/' ];                                        //    8045
                            if ( preg_match( '#/[^/]+/$#', $matches[ 1 ], $matches_2 ) == 1 ) {                                      //    8046
                                return in_array( $matches_2[ 0 ], $includes ) ? $matches[ 0 ] : "<!-- {$matches[ 0 ]} -->";          //    8047
                            }                                                                                                        //    8048
                            return "<!-- {$matches[ 0 ]} -->";                                                                       //    8049
                        }, $menu );                                                                                                  //    8050
                        $id = self::$demo_product_id;                                                                                //    8051
                        if ( $id && preg_match( '#<li.*><a\shref="(.+?)">Home</a></li>#', $menu, $matches ) ) {                      //    8052
                            $host = $matches[ 1 ];                                                                                   //    8054
                            $menu = preg_replace( '#</ul></div>#',                                                                   //    8055
                                                  '<li class="page_item">'                                                           //    8056
                                                    . "<a href=\"{$host}wp-admin/post.php?post={$id}&action=edit\">"                 //    8057
                                                          . __( 'Edit product', 'classic-commerce' )                                 //    8058
                                                    . '</a>'                                                                         //    8059
                                                . '</li>'                                                                            //    8060
                                                . '<li class="page_item">'                                                           //    8061
                                                    . '<a href="' . esc_url( wp_logout_url() ) . '">'                                //    8062
                                                          . __( 'Log Out' )                                                          //    8063
                                                    . '</a>'                                                                         //    8064
                                                . '</li>'                                                                            //    8065
                                            . '</ul></div>',                                                                         //    8066
                                                  $menu );                                                                           //    8067
                        }   # if ( $id && preg_match( '#<li.*><a\shref="(.+?)">Home</a></li>#', $menu, $matches ) ) {                //    8068
                        return $menu;                                                                                                //    8069
                    } );   # MC_Utility::add_filter( 'wp_page_menu', 'mc_xii_wp_page_menu', function( $menu ) {                      //    8070
                }   # if ( ! is_admin() && $demo_user_enabled && self::is_demo_user() ) {                                            //    8071
            } );   # MC_Utility::add_action( 'init', 'mc_xii_init_demo_functions', function() use ( $demo_user_enabled ) {           //    8072
                                                                                                                                     //    8073

### REDACTED lines 8074 -> 8112 redacted,     39 lines redacted. ###

                                                                                                                                     //    8113
        }   # public static function init() {                                                                                        //    8114
                                                                                                                                     //    8115
        public static function is_demo_user( $user = NULL ) {                                                                        //    8116
            $user  = $user ? $user : wp_get_current_user();                                                                          //    8117
            if ( get_class( $user ) !== 'WP_User' ) {                                                                                //    8118
                return FALSE;                                                                                                        //    8119
            }                                                                                                                        //    8120
            $roles = $user->roles;                                                                                                   //    8121
            return $roles ? in_array( self::SAND_BOX, $roles ) : FALSE;                                                              //    8122
        }                                                                                                                            //    8123
                                                                                                                                     //    8124
        public static function login_redirect( $redirect_to, $user ) {                                                               //    8125
            if ( self::is_demo_user( $user ) ) {                                                                                     //    8126
                if ( $demo_product_id = get_user_meta( $user->ID, 'mc_xii_demo_product_id', TRUE ) ) {                               //    8127
                    self::$demo_product_id = $demo_product_id;                                                                       //    8128
                } else {                                                                                                             //    8129
                    self::$demo_product_id = 0;                                                                                      //    8130
                    # Must explicitly set the current user since wp_signon() does not set the current user.                          //    8131
                    wp_set_current_user( $user->ID );                                                                                //    8132
                    self::reset_demo_product( $user );                                                                               //    8133
                }                                                                                                                    //    8134
                return admin_url( 'post.php?post=' . self::$demo_product_id . '&action=edit' );                                      //    8135
            }                                                                                                                        //    8136
            return $redirect_to;                                                                                                     //    8137
        }                                                                                                                            //    8138
                                                                                                                                     //    8139

### REDACTED lines 8140 -> 8482 redacted,    343 lines redacted. ###

                                                                                                                                     //    8483
        public static function fix_admin_menu( $submenu_file ) {                                                                     //    8484
            global $menu;                                                                                                            //    8485
            global $submenu;                                                                                                         //    8486
            # Fix $menu and $submenu using side effects on this filter.                                                              //    8487
            unset( $menu[ 2 ], $menu[ 10 ], $menu[ 70 ] );                                                                           //    8490
            unset( $submenu[ 'edit.php?post_type=product' ] );                                                                       //    8491
            return $submenu_file;                                                                                                    //    8493
        }                                                                                                                            //    8494
    }   # class MC_Simple_Variation_Demo_Functions extends MC_Simple_Variation_Functions {                                           //    8495
                                                                                                                                     //    8496
    MC_Simple_Variation_Demo_Functions::init();                                                                                      //    8497
                                                                                                                                     //    8498
}   # if ( MC_Simple_Variation_Functions::$load_demo_class ) {                                                                       //    8499
                                                                                                                                     //    8500
                                                                                                                                     //    8905
# The wXy preprocessor removes all error_log() statements. So, if you really want to log something use xerror_log().                 //    8906
include_once dirname( __FILE__ ) . '/xerror_log.php';                                                                                //    8907
