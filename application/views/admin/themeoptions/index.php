<?php
/* @var $this AdminController */
/* @var $dataProvider CActiveDataProvider */

// TODO: rename to template_list.php and move to template controller

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('templateOptions');

?>

<?php $bFromSurveyGroup = ($oSurveyTheme->scenario == 'surveygroup')?true:false; ?>
<div class="ls-space margin left-15 right-15 row list-themes">
    <ul class="nav nav-tabs" id="themelist" role="tablist">
        <li class="active"><a href="#surveythemes"><?php eT('Survey themes'); ?></a></li>
        <li><a href="#adminthemes"><?php eT('Admin themes'); ?></a></li>
        <li><a href="#questionthemes"><?php eT('Question themes'); ?></a></li>
    </ul>
    <div class="tab-content">
        <div id="surveythemes" class="tab-pane active">
            <div class="col-lg-12 list-surveys">

                <?php
                    if (!$bFromSurveyGroup){
                        $this->renderPartial('super/fullpagebar_view', array(
                        'fullpagebar' => array(
                            'returnbutton'=>array(
                                'url'=>'index',
                                'text'=>gT('Close'),
                                ),
                            )
                        ));
                        echo '<h3>'.gT('Installed survey themes:').'</h3>';
                    }


                ?>

                <div class="row">
                    <div class="col-sm-12 content-right">

                        <?php $this->widget('bootstrap.widgets.TbGridView', array(
                            'dataProvider' => $oSurveyTheme->search(),
                            'columns' => array(
                                array(
                                    'header' => gT('Preview'),
                                    'name' => 'preview',
                                    'value'=> '$data->preview',
                                    'type'=>'raw',
                                    'htmlOptions' => array('class' => 'col-md-1'),
                                ),

                                array(
                                    'header' => gT('Name'),
                                    'name' => 'template_name',
                                    'value'=>'$data->template_name',
                                    'htmlOptions' => array('class' => 'col-md-2'),
                                ),

                                array(
                                    'header' => gT('Description'),
                                    'name' => 'template_name',
                                    'value'=>'$data->template->description',
                                    'htmlOptions' => array('class' => 'col-md-3'),
                                    'type'=>'raw',
                                ),

                                array(
                                    'header' => gT('Type'),
                                    'name' => 'templates_type',
                                    'value'=>'$data->typeIcon',
                                    'type' => 'raw',
                                    'htmlOptions' => array('class' => 'col-md-2'),
                                ),

                                array(
                                    'header' => gT('Extends'),
                                    'name' => 'templates_extends',
                                    'value'=>'$data->template->extends',
                                    'htmlOptions' => array('class' => 'col-md-2'),
                                ),

                                array(
                                    'header' => '',
                                    'name' => 'actions',
                                    'value'=>'$data->buttons',
                                    'type'=>'raw',
                                    'htmlOptions' => array('class' => 'col-md-1'),
                                ),

                            )));
                        ?>

                    </div>
                </div>

                <?php if (count($oSurveyTheme->templatesWithNoDb) > 0 && !$bFromSurveyGroup):?>
                    <h3><?php eT('Available survey themes:'); ?></h3>
                    <div class="row">
                        <div class="col-sm-12 content-right">

                            <div id="templates_no_db" class="grid-view">
                                <table class="items table">
                                    <thead>
                                        <tr>
                                            <th><?php eT('Preview'); ?></th><th><?php eT('Folder'); ?></th><th><?php eT('Description'); ?></th><th><?php eT('Type'); ?></th><th><?php eT('Extends'); ?></th><th></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($oSurveyTheme->templatesWithNoDb as $oTemplate):?>
                                            <?php // echo $oTemplate; ?>
                                            <tr class="odd">
                                                <td class="col-md-1"><?php echo $oTemplate->preview; ?></td>
                                                <td class="col-md-2"><?php echo $oTemplate->sTemplateName; ?></td>
                                                <td class="col-md-3"><?php echo $oTemplate->config->metadata->description; ?></td>
                                                <td class="col-md-2"><?php eT('XML themes');?></td>
                                                <td class="col-md-2"><?php echo $oTemplate->config->metadata->extends; ?></td>
                                                <td class="col-md-1"><?php echo $oTemplate->buttons; ?></td>
                                            </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>

                            </div>

                        </div>
                    </div>
                <?php endif;?>
            </div>
        </div>
        <div id="adminthemes" class="tab-pane">
            <div class="col-lg-12 list-surveys">
                <h3><?php eT('Available admin themes:'); ?></h3>
                <div class="row">
                    <div class="col-sm-12 content-right">
                        <div id="templates_no_db" class="grid-view">
                            <table class="items table">
                                <thead>
                                    <tr>
                                        <th><?php eT('Preview'); ?></th><th><?php eT('Folder'); ?></th><th><?php eT('Description'); ?></th><th><?php eT('Type'); ?></th><th></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($oAdminTheme->adminThemeList as $oTheme ):?>
                                        <tr class="odd">
                                            <td class="col-md-1"><?php echo $oTheme->preview; ?></td>
                                            <td class="col-md-2"><?php echo $oTheme->metadata->name; ?></td>
                                            <td class="col-md-3"><?php echo $oTheme->metadata->description; ?></td>
                                            <td class="col-md-2"><?php eT('Core admin theme');?></td>
                                            <td class="col-md-1">
                                                <?php if ($oTheme->path == getGlobalSetting('admintheme')):?>
                                                    <h3><strong class="text-info"><?php eT("Selected")?></strong></h3>
                                                <?php else: ?>
                                                    <a href="<?php echo Yii::app()->getController()->createUrl("admin/themeoptions/sa/setAdminTheme/", ['sAdminThemeName'=>$oTheme->path]);?>" class="btn btn-default btn-lg ">
                                                        <?php eT("Select");?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table>

                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div id="questionthemes" class="tab-pane">
            questions
        </div>
    </div>
</div>




<script>
    $('#themelist a').click(function (e) {
        window.location.hash = $(this).attr('href');
        e.preventDefault();
        $(this).tab('show');
    });
    $(document).on('ready pjax:scriptcomplete', function(){
        if(window.location.hash){
            $('#themelist').find('a[href='+window.location.hash+']').trigger('click');
        }
    })
</script>