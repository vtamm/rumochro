var awbMegamenuPosition=function(t){var e,a,r=t.closest("nav"),i=r.hasClass("awb-menu_column")?"column":"row",o=r.hasClass("awb-menu_expand-right")?"right":"left",s="right"===o?"left":"right",u=t.attr("data-width"),l=jQuery(window).width(),n=t.closest("li"),w=t.outerWidth(),h=l-w,d=!jQuery("#wrapper").css("margin").includes("0px auto"),c={},p={};r.hasClass("awb-menu_flyout")||r.hasClass("collapse-enabled")||(w>=l&&(u="viewport"),e=jQuery("#icon-bar").length?jQuery("#icon-bar").outerWidth():jQuery("#wrapper").outerWidth(d)-jQuery("#wrapper").width(),r.is(":visible")?a=n.offset().left-e:(r.css("display","block"),a=n.offset().left-e,r.css("display","")),"row"===i?"site_width"===u||"viewport"===u?t.css({left:-1*a+h/2,right:"auto"}):((p={right:l-a,left:a+n.outerWidth()})[o]>w?(c[s]=0,c[o]="auto"):p[s]>w?(c[o]=0,c[s]="auto"):(c[s]=-1*(w-p[o]),c[o]="auto"),t.css(c)):(p={right:l-(a+n.outerWidth(!0)),left:a},t[0].style.setProperty("--awb-megamenu-maxwidth",p[o]-(t.outerWidth(!0)-w)+"px"),c[s]="100%",c[o]="auto",t.css(c)))};jQuery(window).on("fusion-resize-horizontal awb-position-megamenus load",function(){var t=window.avadaGetScrollBarWidth()-1+"px";jQuery("body")[0].style.setProperty("--awb-scrollbar-width",t),jQuery(".awb-menu .awb-menu__mega-wrap").each(function(){awbMegamenuPosition(jQuery(this))})});