<!DOCTYPE html>
<html>
    <head>
        <title>Erge</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href='https://fonts.googleapis.com/css?family=Roboto:400,700' rel='stylesheet' type='text/css'>
        <link href='https://fonts.googleapis.com/css?family=Lato' rel='stylesheet' type='text/css'>
   </head>
    <body style="max-width: 800px; margin: auto; padding: 25px 0; font-family: 'Roboto', sans-serif;">
        <div style="width: 90%; margin: auto; display: table;">
            <div style="display: table-cell; height: 100%;">
                <table width="100%" background="<?php echo env('BASE_PATH');?>images/header-layer.jpg" style="background-repeat: no-repeat; width: 100%; background-size: 100% 100%;">
                    <tr>
                        <td>
                            <div style="text-align: center; height: 100px; width: 100%;">
                                <div style="background-color: #003672; width: 100%; width: 90%; display: inline-block; margin-top: 5%;">
                                    <h1 style="color: #fff; font-size: 52px; margin: 10px 0; font-weight: 700;">ERGE</h1>
                                </div>
                            </div>
                        </td>
                    </tr>  
                </table>
                
            </div>
        </div>
        <div style="width: 90%; margin: auto; display: table;">
            <div style="display: table-cell; height: 100%;">
                <div style="padding: 25px;">
                    <h1 style="font-size: 30px; color: #2a2a2a; font-weight: 700;">Dear <?php echo $f_name ." ". $l_name; ?></h1>
                    <p style="color: #2a2a2a; font-size: 14px;">
                        You have received amount of <strong><?php echo $amount."$"; ?></strong> on <strong><?php 
                        $amNY = new \DateTime('America/New_York');
                        $estTime = $amNY->format('d - F - Y h:iA T');
                        echo $estTime;
                        
                        ?></strong>. <br>
                        for your service on ERGE. 
                    </p>
                </div>
                <div style="padding: 25px;">
                    <h1 style="font-size: 30px; color: #175891; font-weight: 700;">ERGE Team</h1>
                </div>
                <table style="padding: 25px 10px 0; width: 100%;">
                    <tr>
                        <td style="width: 15%">
                            <span style="line-height: 3.8; display: inline-block; font-weight: 700; font-size: 20px; color: #fff; background-color: #175891; width: 75px; height: 75px; border-radius: 50%; border: 5px solid #f2f2f2; text-align: center;">ERGE</span>
                        </td>
                        <td style="width: 5%; text-align: right;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 20%;"><a href="#"><img style="width: 25px;" src="<?php echo env('BASE_PATH');?>images/facebook.png" alt="Facebook Logo" /></a></td>
                                    <td style="width: 20%;"><a href="#"><img style="width: 25px;" src="<?php echo env('BASE_PATH');?>images/google.png" alt="Google Logo" /></a></td>
                                    <td style="width: 20%;"><a href="#"><img style="width: 25px;" src="<?php echo env('BASE_PATH');?>images/insta.png" alt="Insta Logo" /></a></td>
                                    <td style="width: 20%;"><a href="#"><img style="width: 25px;" src="<?php echo env('BASE_PATH');?>images/twitter.png" alt="Twitter Logo" /></a></td>
                                    <td style="width: 20%;"><a href="#"><img style="width: 25px;" src="<?php echo env('BASE_PATH');?>images/link.png" alt="Link Logo" /></a></td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="width: 90%; margin: auto; display: table;">
            <div style="display: table-cell; height: 100%;">
                <table width="100%" background="<?php echo env('BASE_PATH');?>images/footer-layer.jpg" style="background-repeat: repeat-y no-repeat; background-repeat: no-repeat; width: 100%; background-size: 100% 20px; height: 20px;">
                    <tr><td></td></tr>
                </table>
                <table width="100%">
                    <tr>
                        <td>
                            <div style="width: 100%; z-index: -1;">
                                <div style="text-align: center;">
                                    <h5 style="color: #2a2a2a; font-size: 14px; display: inline-block; margin-top: 50px; font-family: 'Lato', sans-serif;">ERGE © 2016. All rights reserved</h5>
                                </div>                    
                            </div>
                        </td>
                    </tr>
                            
                </table>
            </div>
        </div>
    </body>
</html>
