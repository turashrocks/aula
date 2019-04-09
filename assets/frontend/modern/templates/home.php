<div class="container m-t-30 m-b-30">
	<div id="content">
		<div class="container">
			<section class="bar">
				<div class="row">
					<div class="col-lg-12">
						<div class="heading">
							<h2><?php echo $permalink['page_title']; ?></h2>
						</div>

						<div class="row">
							<div class="<?php if($permalink['page_feat_image'] == ""){ echo "col-sm-12"; }else{ echo "col-sm-8"; } ?>">
								<p class="lead"><?php echo $permalink['page_content']; ?></p>
							</div>
							<?php if(isset($template_vars['page_feat_image']) AND $template_vars['page_feat_image'] != ""){ ?>
								<div class="col-sm-4">
									<img alt="" src="uploads/ml_uploads/<?php echo $template_vars['page_feat_image']['file_uploaded_name']; ?>" class="img-fluid">
								</div>
							<?php } ?>
						</div>

					</div>
				</div>

			</section>
		</div>
	</div>
</div>

<div class="bg-light spacer feature20 up">
    <div class="container">
        <!-- Row  -->
        <div class="row justify-content-center">
            <div class="col-md-7 text-center">
                <h2 class="title"><?php echo $template_vars['language']['latestNews']; ?></h2>
            </div>
        </div>
        <!-- Row  -->
        <div class="row wrap-feature-20">
        	<?php foreach ($template_vars['footer_news_list'] as $value) { ?>
	            <div class="col-lg-6" data-aos="flip-left" data-aos-duration="1200">
	                <div class="card">
	                    <div class="row">
	                        <div class="col-md-8">
	                            <div class="card-body d-flex no-block" style="padding: 10px;">
	                                <div class="m-r-20"><img src="<?php echo \URL::asset('uploads/news/'.$value['newsImage']); ?>" height="74" class="rounded" /></div>
	                                <div>
	                                    <h5 class="font-medium"><?php echo $value['newsTitle']; ?></h5>
	                                </div>
	                            </div>
	                        </div>
	                        <div class="col-md-4 text-center">
	                            <a href="javascript:void(0)" class="text-white linking bg-success-gradiant"><?php echo $template_vars['language']['readMore']; ?>  <i class="ti-arrow-right"></i></a>
	                        </div>
	                    </div>
	                </div>
	            </div>
            <?php } ?>
        
        </div>
        <!-- Row  -->
    </div>
</div>

<div class="mini-spacer bg-info text-white c2a7">
    <div class="container">
        <div class="d-flex">
            <div class="display-7 align-self-center"><?php echo $template_vars['language']['logToMgmtSys']; ?></div>
            <div class="ml-auto m-t-10 m-b-10"><a href="<?php echo \URL::to('/login'); ?>" class="btn btn-outline-light btn-md"><?php echo $template_vars['language']['signIn']; ?></a></div>
        </div>
    </div>
</div>