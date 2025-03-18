<main class="main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="get-started">
                    <div class="get-started-head">
                        <h2> Getting Started</h2>
                        <p> Wel Come! We’re here to help you get things rolling.</p>
                    </div>
                    <div class="get-started-body">
                        <div class="card">
                            <div class="card-body">
                                <ul class="list-started">
                                    <?php foreach ($tourSteps as $tourId => $tour) { ?>
                                        <li class="completed">
                                            <a class="target" href="<?php echo SiteTourHelper::getUrl($tourId); ?>">
                                                <div class="list-started_icon">
                                                    <svg class="svg" width="36" height="36">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-getting-started.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#setup-logo-address">
                                                        </use>
                                                    </svg>
                                                </div>
                                                <div class="list-started_data">
                                                    <h5><?php echo $tour['title']; ?> </h5>
                                                    <p><?php echo $tour['msg']; ?></p>
                                                </div>
                                                <div class="list-started_action">
                                                    <img src="<?php echo CONF_WEBROOT_URL; ?>images/retina/tick-green.svg" width="32" height="32" alt="">
                                                </div>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="get-started-foot">
                        <a href="<?php echo UrlHelper::generateUrl('Home'); ?>">Skip and continue to your Dashboard</a>
                        <p>Tip: You return here any time from the Setting Menu</p>
                    </div>
                </div>

            </div>
        </div>
        <?php /* ?>
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="onboarding">
                    <div class="onboarding-aside">
                        <div class="onboarding-head"></div>
                        <div class="onboarding-body">

                            <ul class="onboarding-nav">
                                <li class="onboarding-nav-item completed">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">General configuration data</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item completed">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">Theme</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item completed">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">Email configuration</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item process">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label"> CMS pages</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item pending">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">Add product</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item pending">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">Payment methods</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item pending">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">Tax Structure</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item pending">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label"> Navigation Management</span>
                                    </a>

                                </li>
                                <li class="onboarding-nav-item pending">
                                    <a class="onboarding-nav-link">
                                        <span class="onboarding-nav-icn"></span>
                                        <span class="onboarding-nav-label">Slides</span>
                                    </a>

                                </li>
                            </ul>
                        </div>
                        <div class="onboarding-foot">
                            <div class="rocket">
                                <img src="<?php echo CONF_WEBROOT_URL; ?>images/icons/rocket-launch.svg" width="290" height="" alt="">
                            </div>

                        </div>

                    </div>
                    <div class="onboarding-main">
                        <div id="frmBlockJs" class="card">
                            <div class="card-head">
                                <div class="card-head-label">
                                    <h3 class="card-head-title">
                                        General Settings </h3>
                                </div>
                                <div class="card-head-toolbar">
                                    <div class="input-group">
                                        <select class="form-control form-select select-language" onchange="getForm(1, this.value)">
                                            <option value="1" selected="">English</option>
                                            <option value="2">Arabic</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="formBodyJs">
                                    <form name="frmConfiguration" method="post" id="frmConfSetting" class="form form--settings modalFormJs checkboxSwitchJs layout--ltr" dir="ltr" data-onclear="getForm(1)" onsubmit="setup($(&quot;#frmConfSetting&quot;)); return(false);">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Site Name</label>
                                                    <div class=""><input data-field-caption="Site Name" data-fatreq="{&quot;required&quot;:false}" type="text" name="CONF_WEBSITE_NAME_1" value="Yo!Kart"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Site Owner</label>
                                                    <div class=""><input data-field-caption="Site Owner" data-fatreq="{&quot;required&quot;:false}" type="text" name="CONF_SITE_OWNER_1" value="Yo!Kart"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group"><label class="label">Cookies Policies Text</label>
                                                    <div class=""><textarea data-field-caption="Cookies Policies Text" data-fatreq="{&quot;required&quot;:false}" name="CONF_COOKIES_TEXT_1">Cookies Policy Text Will go here...</textarea></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Store Owner Email<span class="spn_must_field">*</span></label>
                                                    <div class=""><input data-field-caption="Store Owner Email" data-fatreq="{&quot;required&quot;:true,&quot;email&quot;:true}" type="text" name="CONF_SITE_OWNER_EMAIL" value="yokart@dummyid.com"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Telephone</label>
                                                    <div class=""><input class="phoneJs ltr-right" placeholder="" maxlength="15" data-field-caption="Telephone" data-fatreq="{&quot;required&quot;:false,&quot;user_regex&quot;:&quot;^[0-9]{1,15}$&quot;,&quot;customMessage&quot;:&quot;Please Enter Valid Format.&quot;}" type="text" name="CONF_SITE_PHONE" value="8591919191"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Fax</label>
                                                    <div class=""><input class="phoneJs ltr-right" placeholder="" maxlength="15" data-field-caption="Fax" data-fatreq="{&quot;required&quot;:false,&quot;user_regex&quot;:&quot;^[0-9]{1,15}$&quot;,&quot;customMessage&quot;:&quot;Please Enter Valid Format.&quot;}" type="text" name="CONF_SITE_FAX" value="9555596666"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">About Us</label>
                                                    <div class=""><select data-field-caption="About Us" data-fatreq="{&quot;required&quot;:false}" name="CONF_ABOUT_US_PAGE">
                                                            <option value="">Select</option>
                                                            <option value="1" selected="selected">About Us</option>
                                                            <option value="2">Terms &amp; Conditions</option>
                                                            <option value="3">Privacy Policy</option>
                                                            <option value="4">test page</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Privacy Policy Page</label>
                                                    <div class=""><select data-field-caption="Privacy Policy Page" data-fatreq="{&quot;required&quot;:false}" name="CONF_PRIVACY_POLICY_PAGE">
                                                            <option value="">Select</option>
                                                            <option value="1">About Us</option>
                                                            <option value="2">Terms &amp; Conditions</option>
                                                            <option value="3" selected="selected">Privacy Policy</option>
                                                            <option value="4">test page</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Terms And Conditions Page</label>
                                                    <div class=""><select data-field-caption="Terms And Conditions Page" data-fatreq="{&quot;required&quot;:false}" name="CONF_TERMS_AND_CONDITIONS_PAGE">
                                                            <option value="">Select</option>
                                                            <option value="1">About Us</option>
                                                            <option value="2" selected="selected">Terms &amp; Conditions</option>
                                                            <option value="3">Privacy Policy</option>
                                                            <option value="4">test page</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Gdpr Policy Page</label>
                                                    <div class=""><select data-field-caption="Gdpr Policy Page" data-fatreq="{&quot;required&quot;:false}" name="CONF_GDPR_POLICY_PAGE">
                                                            <option value="">Select</option>
                                                            <option value="1">About Us</option>
                                                            <option value="2">Terms &amp; Conditions</option>
                                                            <option value="3" selected="selected">Privacy Policy</option>
                                                            <option value="4">test page</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">Cookies Policies Page</label>
                                                    <div class=""><select data-field-caption="Cookies Policies Page" data-fatreq="{&quot;required&quot;:false}" name="CONF_COOKIES_a_LINK">
                                                            <option value="">Select</option>
                                                            <option value="1">About Us</option>
                                                            <option value="2" selected="selected">Terms &amp; Conditions</option>
                                                            <option value="3">Privacy Policy</option>
                                                            <option value="4">test page</option>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">&nbsp;</label>
                                                    <div class="setting-block"><label class="switch switch-sm switch-icon"><input data-field-caption="Header Mega Menu" data-fatreq="{&quot;required&quot;:false}" type="checkbox" name="CONF_LAYOUT_MEGA_MENU" value="1" checked="checked"><span class="input-helper"></span>Header Mega Menu</label></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">&nbsp;</label>
                                                    <div class="setting-block"><label class="switch switch-sm switch-icon"><input data-field-caption="Home Page Loader" data-fatreq="{&quot;required&quot;:false}" type="checkbox" name="CONF_LOADER" value="1" checked="checked"><span class="input-helper"></span>Home Page Loader</label></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group"><label class="label">&nbsp;</label>
                                                    <div class="setting-block"><label class="switch switch-sm switch-icon"><input data-field-caption="Cookies Policies" data-fatreq="{&quot;required&quot;:false}" type="checkbox" name="CONF_ENABLE_COOKIES" value="1" checked="checked"><span class="input-helper"></span>Cookies Policies</label><span class="form-text text-muted">Cookies Policies Section Will Be Shown On Frontend</span></div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group"><label class="label">Google Map Iframe</label>
                                                    <div class=""><textarea data-field-caption="Google Map Iframe" data-fatreq="{&quot;required&quot;:false}" name="CONF_MAP_IFRAME_CODE">&lt;iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3432.244399529664!2d76.72417851490127!3d30.655245696414436!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390feef5b90fc51b%3A0x7541e61fcad7e6c4!2sAbly%20Soft%20Pvt.%20Ltd.!5e0!3m2!1sen!2sin!4v1608632597647!5m2!1sen!2sin" width="600" height="450" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"&gt;&lt;/iframe&gt;</textarea><span class="form-text text-muted">This Is The Gogle Map Iframe Script, Used To Display Google Map On Contact Us Page</span></div>
                                                </div>
                                            </div>
                                        </div><input data-field-caption="" data-fatreq="{&quot;required&quot;:false}" type="hidden" name="CONF_SITE_PHONE_dcode" value="+91-in"><input data-field-caption="" data-fatreq="{&quot;required&quot;:false}" type="hidden" name="CONF_SITE_FAX_dcode" value="+91-in"><input data-field-caption="" data-fatreq="{&quot;required&quot;:false}" type="hidden" name="form_type" value="1"><input data-field-caption="" data-fatreq="{&quot;required&quot;:false}" type="hidden" name="lang_id" value="1">
                                    </form>
                                    <script>
                                        frmConfiguration_validator_formatting = {
                                            "errordisplay": 3,
                                            "summaryElementId": ""
                                        };
                                        frmConfiguration_validator = $("#frmConfSetting").validation(frmConfiguration_validator_formatting);
                                    </script>
                                </div>
                            </div>

                            <div class="card-foot">
                                <div class="row justify-content-center">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col">
                                                <a name="btn_reset_form" class="btn btn-outline-brand resetModalFormJs">Reset</a>
                                            </div>
                                            <div class="col-auto">
                                                <a name="btn_save" class="btn btn-brand gb-btn gb-btn-primary submitBtnJs">Save</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
 <?php  */?>