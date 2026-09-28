<?php

/*************************************************************************
Constants
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 16/04/2012
Coder: Mai Minh (http://maiminh.vnweblogs.com)
 **************************************************************************/
# Please change carefully
# Maintenance options
define('MAINTENANCE', '0');                    # Maintenance all estores

# Debug options
define('DEBUG', '1');                        # Debug mode
define('DEBUG_DISPLAY', '1');                # Display debug information
// define('DEBUG_IP', '115.78.234.252');        # This IP will see the debug information
define('DEBUG_IP', '115.75.191.44');
// define('DEBUG_IP', '103.119.85.189');
// define('DEBUG_IP', '14.226.244.70');
// define('DEBUG_IP', '58.187.191.184');
define('DEBUG_FILE_APPEND', 8);                # Debug file write mode - 8 for append, 0 for overwrite

#Database settings
define('DB_PREFIX', 'dc_');                    # Table prefix
define('QUERY_ERROR', '1');                    # Show query if failed - Only for debug
define('QUERY_DEBUG', '1');                    # Database debug - Show query if failed - Only for debug
define('SHOW_QUERY', '0');                    # Show queries
define('TIME_ZONE', 'Asia/Saigon');            # Default timezone

# Key Setting
define('APP_KEY', 'D3raCMSver30');            # App key for special requests (like crontab,...)

# Financial settings
define('DEFAULT_RATE', '20800');            # Default USD/VND rate

# Template settings
define('TEMPLATE_PATH', 'templates');        # Template path
define('TEMPLATE_COMPILE', true);            # Force rcompile template files
define('TEMPLATE_DEBUG', false);            # Template debug
define('DEFAULT_TEMPLATE', 'default');        # Default estore template
define('STANDARD_TEMPLATE', 'standard');        # Standard estore template
define('FRONT_TEMPLATE', 'main');            # Front-end template
define('ADMIN_TEMPLATE_COMPILE', true);        # Force recompile admin template files
define('ADMIN_TEMPLATE_DEBUG', false);        # Admin template debug
define('ADMIN_DEFAULT_TEMPLATE', 'admin');    # Default CMS template

# URL settings
define('URL_TYPE', 2);                        # URL type: 1- query string, 2- SEO
define('SUB_DOMAIN', 1);                    # Support sub domain
define('PROTOCOL', 'https://');                # Protocol 'http://' or 'https://'
define('ECOMMERCE_PROTOCOL', 'http://');    # Order, payment protocol 'http://' or 'https://'
define("SITE", "DeraCMS 3.0");                # Main site name
define('DOMAIN', 'dung.derasoft.com');        # Main domain name
define('SCRIPT', 'index.php');                # Script name
define('ADMIN_SCRIPT', 'admin.php');        # Admin script name	
define('ADMINCP_SCRIPT', 'admincp.php');    # Admincp script name

# Language settings
define('DEFAULT_CHARSET', 'utf-8');            # Default charset
define('DEFAULT_LANGUAGE', 'vn');            # Default language
define('DEFAULT_ADMIN_LANGUAGE', 'vn');        # Default language

# Operation settings
define('DEFAULT_OP', 'main');                # Default operation if error
define('DEFAULT_ACT', 'index');                # Default action if error
define('DEFAULT_ADMIN_OP', 'login');        # Default operation if error
define('DEFAULT_ROWS_PER_PAGE', 20);            # Number rows per page in front page
define('DEFAULT_ITEMS_PER_ROW', 3);            # Number items per row in front page
define('DEFAULT_ADMIN_ROWS_PER_PAGE', 20);    # Number rows per page in Admin panel

# Upload settings
define('KEEP_ORIGINAL_IMAGE_FILE',1);		# 1-Keep original image file; 0-delete orginal image file
define('ALLOW_FILE_TYPES', 'jpg$|jpeg$|png$|doc$|docx$|xls$|xlsx$|ppt$|txt$|zip$|rar$|pdf$|svg$|gif$|bmp$|mp4$|flv$|f4v$|wmv$|swf$|webp$|csv$');
define('IMAGE_TYPES', 'jpg$|jpeg$|bmp$|gif$|png$|svg$|webp$');    # Images file type
define('VIDEO_TYPES', 'mp4$|flv$|f4v$|wmv$|swf$');    # Video file type
define('MUSIC_TYPES', 'wma$|wav$|mp3$|asf$');    # Music file type
define('DOCUMENT_TYPES', 'doc$|docx$|xls$|xlsx$|ppt$|pptx$|txt$|pdf$|csv$');    # Document file type
define('ALLOW_BANNER_TYPES', 'jpg$|jpeg$|bmp$|gif$|png$|mp4$|flv$|f4v$|wmv$|swf$|svg$|webp$');    # Allow banner file type
define('MAX_BANNER_SIZE', '10000');          # Max banner file size 10MB
define('DEFAULT_PHOTO_FORMAT', 'jpg');       # Photo format, only set to jpg, png, gif, webp
define('DEFAULT_PHOTO_QUALITY', '90');       # Photo quality, only used for JPG
define('CREATE_LARGE_IMAGE', 1);        	 # Resize for large image
define('DEFAULT_LARGE_SIZE', '1024');        # Default large width or height
define('DEFAULT_LARGE_SQUARE', '0');         # Create square large
define('CREATE_MEDIUM_IMAGE', 1);        	 # Resize for medium image
define('DEFAULT_MEDIUM_SIZE', '300');        # Default medium width or height
define('DEFAULT_MEDIUM_SQUARE', '1');        # Create square medium
define('CREATE_THUMBNAIL_IMAGE', 1);         # Resize for thumbnail image
define('DEFAULT_THUMBNAIL_SIZE', '160');     # Default thumbnail width or height
define('DEFAULT_THUMBNAIL_SQUARE', '1');     # Create square thumbnail
define('CREATE_AVATAR_IMAGE', 1);        	 # Resize for avatar image
define('DEFAULT_AVATAR_SIZE', '100');        # Default avatar width or height
define('DEFAULT_AVATAR_SQUARE', '1');        # Create square avatar
define('CREATE_PRODUCT_AVATAR_CORNER', '0'); # Create corner for avatar. Image should be PNG
define('DEFAULT_DIR_CHMODE', 0777);          # Default chmod for new directory
define('GALLERY_FOLDER', 'upload');          # Upload root folder
define('UPLOAD_SIZE_BYTES', 1);              # File Size: Bytes
define('UPLOAD_SIZE_MBYTES', 2);             # File Size: Megabytes
define('UPLOAD_ERROR_NOFILE', 1);            # Error Code: No file selected
define('UPLOAD_ERROR_SIZE', 2);              # Error Code: File exceeds the file size limit
define('UPLOAD_ERROR_TYPE', 3);              # Error Code: File Type is invalid
define('UPLOAD_ERROR_MOVING', 4);            # Error Code: Error moving file


# Email settings
define('SMTP_MAIL', '1');                    # 1 - Send email using SMTP, 0 - Send email using PHP_MAIL
define('SMTP_HOSTNAME', 'dung.derasoft.com');  # Co www
define('SMTP_HOST', 'mail.dung.derasoft.com');    # SMTP host
define('SMTP_PORT', '25');                    # SMTP port
define('SMTP_SSL', '0');                        # 1 - SMTP SSL, 0 Normal
define('SMTP_USER', 'no-reply@dung.derasoft.com');    # SMTP username
define('SMTP_PASSWORD', 'PwJxyW9VUkTDWbbwmHd6');        # SMTP password
define('ADMIN_EMAIL', 'info@dung.derasoft.com');    # Admin's email

# Other settings
define('SESSION_TIME', '30');                # Number of minutes session remaining
define('EXCECUTE_DAYS', '3');                # Maximum days allowed to exceute an action, e.g forgot password or process order
define('MAX_GRACE_TIME', '5');                # So phut dem so lan user dang nhap sai
define('MAX_FAIL_TIMES', '5');                # Maximum fail login attempts

# User types
define('U_GUEST', '0');                        # Guest
define('U_SITE_STAFF', '1');                    # Site staff
define('U_SITE_ADMIN', '2');                    # Site admin
define('U_SITE_FOUNDER', '3');                # Site founder
define('U_SITE_MEMBER', '4');                # Site member
define('U_BIDO_SALE', '5');                    # Salesperson of BiDo.vn
define('U_BIDO_BILLING', '6');                # Accountant of BiDo.vn
define('U_BIDO_STAFF', '7');                    # Staff of BiDo.vn
define('U_BIDO_ADMIN', '8');                    # Administrator of BiDo.vn
define('U_BIDO_FOUNDER', '9');                # Founder of BiDo.vn

# Store
define('NOT_ALLOW_STORE', '^admin$|^administrator$|^webmaster$|^www$|^secure$|^mail$|^ftp$|^webmail$|^smtp$|^pop$|^bqt$|^banquantri$|^founder$|^payment$|^corp$|^corporate$|^quantri$|^shop$|^cuahang$|^estore$');                # Not allow these store names to register
define('NOT_ALLOW_USERNAME', '^admin$|^administrator$|^webmaster$|^www$|^bqt$|^banquantri$|^founder$|^corp$|^corporate$|^quantri$');                # Not allow these usernames to register

# Status
define('S_DISABLED', '0');                    # Disabled
define('S_ENABLED', '1');                    # Enabled
define('S_DELETED', '2');                    # Deleted
define('S_WAITING', '3');                    # Waiting for approval
define('S_EXPIRED', '4');                    # Expired

#Status Order - version 3.0
define('S_DISABLED_ORDER', '0');                    # Disabled
define('S_COMPLETE_ORDER', '1');                    # Completed
define('S_DELETED_ORDER', '2');                    # Deleted
define('S_UNPAID_ORDER', '3');                    # Unpaid
define('S_PAID_ORDER', '4');                        # Paid
define('S_DELIVE_ORDER', '5');                    # Delive
define('S_DELIVED_ORDER', '6');                    # Delived
define('S_CANCELED_ORDER', '7');                    # Canceled

# Order status - version 4.0
define('ORDER_STATUS_DISABLED', '0');                    # Disabled
define('ORDER_STATUS_COMPLETED', '1');                   # Completed
define('ORDER_STATUS_NEW', '2');    	                 # New
define('ORDER_STATUS_PROCESSING', '3');                  # Processing
define('ORDER_STATUS_CANCELED', '4');                    # Canceled
define('ORDER_STATUS_DELETED', '5');                     # Deleted

# Order payment method
define('ORDER_PAYMENT_METHOD_CASH', '1');                    # Cash
define('ORDER_PAYMENT_METHOD_TRANSFER', '2');                 # Transfer
define('ORDER_PAYMENT_METHOD_COD', '3');                     # Cash on Delivery
define('ORDER_PAYMENT_METHOD_NAPAS', '4');                   # NAPAS
define('ORDER_PAYMENT_METHOD_CC', '5');                      # Credit card
define('ORDER_PAYMENT_METHOD_GW', '6');                    # Payment gateway

# Order payment status
define('ORDER_PAYMENT_STATUS_PENDING', '0');                    # Pending
define('ORDER_PAYMENT_STATUS_PAID', '1');                 # Paid
define('ORDER_PAYMENT_STAUS_OVER_DUE', '2');                     # Over due 
define('ORDER_PAYMENT_STATUS_FAIL', '3');                   # Fail

# Order delivery vendor
define('ORDER_DELIVERY_VENDOR_SELF', '1');
define('ORDER_DELIVERY_VENDOR_GRAB', '2');
define('ORDER_DELIVERY_VENDOR_XANHSM', '3');
define('ORDER_DELIVERY_VENDOR_VIETTEL', '4');
define('ORDER_DELIVERY_VENDOR_GHN', '5');
                    
# Order delivery status
define('ORDER_DELIVERY_STATUS_PENDING', '0');
define('ORDER_DELIVERY_STATUS_COMPLETED', '1');
define('ORDER_DELIVERY_STATUS_PREPARING', '2');
define('ORDER_DELIVERY_STATUS_SHIPPING', '3');
define('ORDER_DELIVERY_STATUS_NOSTOCK', '4');

#PA vietnam API
define('PA_USERNAME','digitaltrust');
define('PA_API_KEY','15a46acc9e955a158fc666b8e036ff3e');
define('PA_API_URL','https://daily.pavietnam.vn/interface.php');

?>
