<?php

require VENDI_DEV_BACKUP_PATH . '/vendor/autoload.php';

spl_autoload_register(
                        function ( $class )
                        {
                            //PSR-4 compliant autoloader
                            //See http://www.php-fig.org/psr/psr-4/
                            $prefixes = array(
                                                'Vendi\\InternalTools\\DevServerBackup\\' => VENDI_DEV_BACKUP_PATH . '/src/',
                                            );

                            foreach( $prefixes as $prefix => $base_dir )
                            {
                                // does the class use the namespace prefix?
                                $len = strlen( $prefix );
                                if ( 0 !== strncmp( $prefix, $class, $len ) )
                                {
                                    // no, move to the next registered prefix
                                    continue;
                                }

                                // get the relative class name
                                $relative_class = substr( $class, $len );

                                // replace the namespace prefix with the base directory, replace namespace
                                // separators with directory separators in the relative class name, append
                                // with .php
                                $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

                                // if the file exists, require it
                                if ( file_exists( $file ) )
                                {
                                    require $file;
                                }
                            }
                        }
                    );
