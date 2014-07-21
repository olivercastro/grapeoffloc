 (function(a){
                                                a.fn.webwidget_rating_bar=function(p){
                                                    var p=p||{};

                                                    var b=p&&p.rating_star_length?p.rating_star_length:"5";
                                                    var c=p&&p.rating_function_name?p.rating_function_name:"";
                                                    var f=p&&p.rating_default_value?p.rating_default_value:"0";
                                                    //var d=p&&p.directory?p.directory:"images";
                                                    var d='http://www.grapeoff.com/skin/frontend/default/default/images/web_widget_star.gif';
                                                    var g="";
                                                    var h="";
                                                    var j="";
                                                    var k="";
                                                    var l="";
                                                    var m="";
                                                    var n="";
                                                    var o="";
                                                    var q=a(this);
                                                    b=parseInt(b);
                                                    //parseFloat(o).toFixed(1)
                                                    init();
                                                    q.next("ul").children("li").hover(function(){
                                                        jQuery(this).parent().children("li").children("div").width(0);
                                                        var a=jQuery(this).parent().children("li").index(jQuery(this));
                                                        jQuery(this).parent().children("li").children("div").slice(0,a).width(25)
                                                        });
                                                    q.next("ul").children("li").mousemove(function(e){
                                                        var a=jQuery(this).parent().children("li").index(jQuery(this));
                                                        if(a*g+g*(n/25)>99){
                                                            o=100;
                                                            }else if(a*g+g*(n/25)<1){
                                                            o=0;
                                                            }else{
                                                            //o=parseInt(a*g+g*(n/25))
                                                            o=a*g+g*(n/25);
                                                            }
                                                            q.val(parseFloat(o/20).toFixed(1));

                                                        n=e.clientX-jQuery(this).offset().left;
                                                        jQuery(this).children("div").width(n)
                                                        });
                                                    q.next("ul").children("li").click(function(){
                                                        eval(c+"("+o+")");
                                                        set_value(o);
                                                        });
                                                    q.next("ul").hover(function(){},function(){
                                                        if(h==""){
                                                            jQuery(this).children("li").children("div").width(0)
                                                            }else{
                                                            set_value(h);
                                                            }
                                                        });
                                                function init(){
                                                    jQuery('<div style="clear:both;"></div>').insertAfter(q);
                                                    q.css("float","right");
                                                    q.css("margin-top","6px");
                                                    var a=jQuery("<ul>");
                                                    a.attr("class","webwidget_rating_bar");
                                                    for(var i=1;i<=b;i++){
                                                        a.append('<li style="background-image:url('+d+')"><div></div></li>')
                                                        }
                                                        a.insertAfter(q);
                                                        
                                                    q.next("ul").children("li").children("div").css('background-image','url('+d+')');
                                                    q.next("ul").children("li").children("div").css('background-position','0px -50px');
                                                    if(f!=""){
                                                        set_value(f);
                                                        }else{
                                                        q.next("ul").children("li").children("div").width(0);
                                                        }
                                                    }
                                                function set_value(a){
                                                g=100/b;
                                                j=Math.floor(a/g);
                                                k=a%g;
                                                l=k/g;
                                                m=25*l;
                                                h=a;
                                                q.val(parseFloat(a/20).toFixed(1));
                                                abc(parseInt(a)+ parseInt(15));
                                                q.next("ul").children("li").children("div").width(0);
                                                q.next("ul").children("li").children("div").slice(0,j).width(25);
                                                q.next("ul").children("li").children("div").eq(j).width(m);
                                                }
                                            }
                                            })(jQuery);