$(document).ready(function()
{
	//slides the element with class "menu_body" when paragraph with class "menu_head" is clicked 
	$("#jq_slide_by_click p.menu_head").click(function()
    {
		$(this).next("div.menu_body").slideToggle(300).siblings("div.menu_body").slideUp("slow");
	});
	
	//slides
	$("#jq_slide_by_click div.content_row_a_toggle_head").click(function()
    {
		$(this).next("div.content_row_body").slideToggle(300).siblings("div.content_row_body").slideUp("slow");   	
	});

	//slides
	$("#jq_slide_by_click div.content_row_a_1_toggle_head").click(function()
    {
		$(this).next("div.content_row_body").slideToggle(300).siblings("div.content_row_body").slideUp("slow");   	
	});

	$("#jq_slide_by_click div.content_row_b_toggle_head").click(function()
    {
		$(this).next("div.content_row_body").slideToggle(300).siblings("div.content_row_body").slideUp("slow");   	
	});
	
	//slides the element with class "menu_body" when mouse is over the paragraph
	$("#jq_slide_by_over p.menu_head").mouseover(function()
    {
	     $(this).next("div.menu_body").slideDown(500).siblings("div.menu_body").slideUp("slow");
	});
});