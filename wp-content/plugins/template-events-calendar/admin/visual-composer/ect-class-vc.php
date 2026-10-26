<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!class_exists('EctVCAddon')) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
    class EctVCAddon
    {
        /**
         * The Constructor
         */
        public function __construct()
        {
            // We safely integrate with VC with this hook
            add_action( 'init', array($this, 'ect_vc_addon' ) );
        }

        function ect_vc_addon(){
           
                $terms = get_terms(array(
                    'taxonomy' => 'tribe_events_cat',
                    'hide_empty' => false,
                ));
                $ect_categories=array();
                $ect_categories['all'] = esc_html(__('all','template-events-calendar'));
        
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    foreach ($terms as $term) {
                        $ect_categories[$term->name] =$term->slug;
                    }
                }
               $date_formats= array(
                   
                esc_html(__( 'Default (01 January 2019)', 'template-events-calendar' ))=>'default',
                esc_html(__( 'Md,Y (Jan 01, 2019)', 'template-events-calendar' ))=>'MD,Y',
                esc_html(__( 'Fd,Y (January 01, 2019)', 'template-events-calendar' ))=>'FD,Y',
                esc_html(__( 'dM (01 Jan))', 'template-events-calendar' ))=> 'DM',
                esc_html(__( 'dMl (01 Jan Monday)', 'template-events-calendar' ))=>'DML',
                esc_html(__( 'dF (01 January)', 'template-events-calendar' ))=>'DF',
                esc_html(__( 'Md (Jan 01)', 'template-events-calendar' ))=>'MD',
                esc_html(__( 'Md,YT (Jan 01, 2019 8:00am-5:00pm)', 'template-events-calendar') )=> 'MD,YT',
                esc_html(__( 'Full (01 January 2019 8:00am-5:00pm)', 'template-events-calendar') )=>'full',
                esc_html(__( 'jMl', 'template-events-calendar' ))=> 'jMl',
                esc_html(__( 'd.FY (01. January 2019)', 'template-events-calendar' ))=>'d.FY',
                esc_html(__( 'd.F (01. January)', 'template-events-calendar') )=>'d.F',
                esc_html(__( 'ldF (Monday 01 January)', 'template-events-calendar') )=>'ldF',
                esc_html(__( 'Mdl (Jan 01 Monday)', 'template-events-calendar' ))=>'Mdl',
                esc_html(__( 'd.Ml (01. Jan Monday)', 'template-events-calendar' ))=>'d.Ml',
                esc_html(__( 'dFT (01 January 8:00am-5:00pm)', 'template-events-calendar' ))=>  'dFT',
                 
                    );
                    $templates=  array(
                      esc_html(__( "Default List Layout",'template-events-calendar' )) => "default",
                      esc_html( __( "Timeline Layout",'template-events-calendar')) => "timeline-view",
                      esc_html( __(  'Minimal List','template-events-calendar')) => 'minimal-list',
                               
                            );
                            $styles=  array(
                              esc_html(__( "Style 1",'template-events-calendar' )) => "style-1",
                              esc_html(__( "Style 2",'template-events-calendar')) => "style-2",
                              esc_html(__( "Style 3",'template-events-calendar')) => "style-3",
                               
                            );

             
                vc_map(array(
                    "name" => esc_html(__("The Events Calendar Shortcode", 'template-events-calendar')),
                    "base" => "events-calendar-templates",
                    "class" => "",
                    "controls" => "full",
                     "icon" => plugins_url('../../assets/images/ect-icons.svg', __FILE__), // or css class name which you can reffer in your css file later. Example: "cool-timeline_my_class"
                    "category" => __('The Events Calendar Shortcode', 'template-events-calendar'),
                   "params" => array(
                        array(
                            "type" => "dropdown",
                            "class" => "",
                            "heading" => esc_html(__( "Select Events Category",'template-events-calendar')),
                            "param_name" => "category",
                            "value" =>$ect_categories,
                           

                            'save_always' => true,
                        ),
                    array(
                            "type" => "dropdown",
                         "class" => "",
                           "heading" => __( "Select Templates",'template-events-calendar'),
                             "param_name" => "template",
                            "value" => $templates,
                           
                    
                             'save_always' => true,
                         ),
                        
                         array(
                            "type" => "dropdown",
                         "class" => "",
                           "heading" => esc_html(__( "Select Styles",'template-events-calendar')),
                             "param_name" => "style",
                            "value" => $styles,
                           
                 
                             'save_always' => true,
                         ),
                         array(
                            "type" => "dropdown",
                            "class" => "",
                            "heading" => esc_html(__( "Date Format",'template-events-calendar')),
                          
                            "param_name" => "date_format",
                            "value" =>$date_formats,
                           

                            'save_always' => true,
                        ),
                         array(
                            "type" => "dropdown",
                         "class" => "",
                           "heading" => __( "Events Order",'template-events-calendar'),
                             "param_name" => "order",
                             "value" => array(
                              esc_html(__( "ASC",'template-events-calendar' )) => "ASC",
                              esc_html(__( "DESC",'template-events-calendar')) => "DESC",
                                            
                                           ),
                            
                               'save_always' => true,
                         ),
                         array(
                            "type" => "dropdown",
                         "class" => "",
                           "heading" => esc_html(__( "Hide Venue",'template-events-calendar')),
                             "param_name" => "hide-venue",
                             "value" => array(
                              esc_html(__( "no",'template-events-calendar' )) => "no",
                              esc_html( __( "Yes",'template-events-calendar')) => "yes",
                                            
                                           ),
                           
                             'save_always' => true,
                         ),
                         array(
                            "type" => "dropdown",
                         "class" => "",
                           "heading" => __( "Enable Social Share Buttons",'template-events-calendar'),
                             "param_name" => "socialshare",
                             "value" => array(
                              esc_html(__( "no",'template-events-calendar' )) => "no",
                              esc_html(__( "Yes",'template-events-calendar')) => "yes",
                                            
                                           ),
                           
                             'save_always' => true,
                         ),
                         array(
                            "type" => "dropdown",
                         "class" => "",
                           "heading" => __( "Show Events",'template-events-calendar'),
                             "param_name" => "time",
                             "value" => array(
                              esc_html(__( "Upcoming Events",'template-events-calendar' )) => "future",
                              esc_html(__( "Past Events",'template-events-calendar')) => "past",
                              esc_html(__( "All (Upcoming + Past)",'template-events-calendar')) => "all",
                                            
                                           ),
                            
                           
                   
                             'save_always' => true,
                         ),
                         array(
                            "type" => "textfield",
                         "class" => "",
                           "heading" => esc_html(__( "Limit the events",'template-events-calendar')),
                             "param_name" => "limit",
                             "value" => '10',
                           
                             'save_always' => true,
                         ),
                         array(
                            "type" => "textfield",
                         "class" => "",
                           "heading" => esc_html(__( "Start Date | format(YY-MM-DD)",'template-events-calendar')),
                             "param_name" => "start_date",
                             "value" => '',
                           
                             'save_always' => true,
                         ),
                         array(
                            "type" => "textfield",
                         "class" => "",
                           "heading" => esc_html(__( "End Date | format(YY-MM-DD)",'template-events-calendar')),
                             "param_name" => "end_date",
                             "value" => '',
                           
                             'save_always' => true,
                         ),

                 

                    )
                ));



            }
        }// vc function end
    
}
new EctVCAddon();