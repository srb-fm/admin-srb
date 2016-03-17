const XLINYLIN=0;   XLOGYLIN=1;     XLINYLOG=2;    XLOGYLOG=3;
const KREUZ=1;  RAUTE=2;  KASTEN=4;  DELTA=8;  PUNKT=16;  PLUS=32; KREIS=64;

var c=document.getElementById("myCanvas");
var ctx=c.getContext("2d");
var farbe,xwork,ywork,wwork,hwork;
var diagrammtyp,marktyp,markgroesse;
var xachse,yachse,titel;

var _t0, _xmin,_ymin,_xmax,_ymax,_xstep,_ystep;
    _dia=false;
        
const SCHWARZ="BLACK";BLAU="BLUE"; GRUEN= "GREEN"; BLAUGRUEN="CYAN";
const ROT="RED";LILA=  "MAGENTA"; ORANGE="orange"; HELLGRAU="LIGHTGRAY";
const GRAU="DARKGRAY"; HELLBLAU="LIGHTBLUE";HELLGRUEN="LIGHTGREEN";
const HELLBLAUGRUEN="LIGHTCYAN";HELLROT= "LIGHTRED";ROSA="LIGHTMAGENTA";
const GELB="YELLOW";WEISS="WHITE";
const AN = true; AUS = false;

function log(x){return Math.log(x)/Math.LN10;}

function Grafik(ein)
{xachse="X-Achse"; yachse="Y-Achse";titel="Titel";
 diagrammtyp=XLINYLIN;
 ctx.font="italic bold 14px Arial";
 ctx.lineWidth=1;
 markgroesse=3;
 marktyp=KREIS;
 _dia=false;
 _xmin=0.1;_xmax=100.0;_xstep=10.0;
 _ymin=0.1;_ymax=100.0;_ystep=10.0;
  xwork=60; ywork=40; wwork=ctx.canvas.width-2*xwork;     	hwork=ctx.canvas.height-2*ywork;
  //printxy(10,10,10);
  farbe =SCHWARZ; cls();
  farbe= WEISS;
}

function hintergrund()
{ctx.fillStyle=farbe;
 ctx.fillRect(xwork,ywork,wwork,hwork);
}

function cls()
{ctx.fillStyle=farbe;
 ctx.fillRect(0,0,ctx.canvas.width,ctx.canvas.height);
}

function printxy(x,y,s)
{//getbackgroundcolor?
 //ctx.fillRect(x,y, ctx.measureText(s).width, ctx.measureText(s).height);
 ctx.fillStyle=farbe;
 //ctx.textBaseline="top";// SetTextJustify(Lefttext,Toptext);
 //ctx.textAlign="left";
 ctx.fillText(s,x,y);
}

function draw(x0,y0, x1, y1, color)
{ctx.beginPath();
 ctx.moveTo(x0,y0);ctx.lineTo(x1,y1);
 ctx.strokeStyle=color;
 ctx.stroke();
}

function plot(x,y,color)
{draw(x,y,x+1,y+1,color);
}

// Diagramm

function DiaText(x,y,s)
{ctx.textAlign="center";
 printxy(xplot(x),yplot(y),s);
}

function DiaPunkt(x,y)
{if(_dia)
 {ctx.strokeStyle=farbe;
  mark(xplot(x),yplot(y));
 }
 else printxy(10,10,"Erst DIAGRAMM Aufrufen!");
}

function DiaLinie(x1,y1,x2,y2)
{ctx.strokeStyle=farbe;
 draw(xplot(x1),yplot(y1),xplot(x2),yplot(y2));
}

function mark(x,y)
{var s=markgroesse;
 
 if(marktyp & KREUZ)
 {draw(x-s,y-s,x+s,y+s,farbe);
  draw(x+s,y-s,x-s,y+s,farbe);
 }
 if(marktyp & RAUTE)
 {draw(x,y-s,x+s,y,farbe);
  draw(x+s,y,x,y+s,farbe);
  draw(x,y+s,x-s,y,farbe);
  draw(x-s,y,x,y-s,farbe);
 }
 if(marktyp & KASTEN)
 {draw(x-s,y-s,x+s,y-s,farbe);
  draw(x+s,y-s,x+s,y+s,farbe);
  draw(x+s,y+s,x-s,y+s,farbe);
  draw(x-s,y+s,x-s,y-s,farbe);
 }
 if(marktyp & DELTA)
 {draw(x,y-s,x+s,y+s,farbe);
  draw(x+s,y+s,x-s,y+s,farbe);
  draw(x-s,y+s,x,y-s,farbe);
 }
 if(marktyp & PUNKT)plot(x,y,farbe);
 if(marktyp & PLUS)
 {draw(x-s,y,x+s,y,farbe);
  draw(x,y-s,x,y+s,farbe);
 }
 if(marktyp & KREIS)
 {ctx.beginPath();
  ctx.arc(x,y,s,0,2*Math.PI);
  ctx.stroke();
 }
}

function xplot(wert)
{var x=0.0;
 if(_dia)
 {switch(diagrammtyp)
  {case XLINYLIN:
   case XLINYLOG: x=(wert-_xmin)/(_xmax-_xmin);break;
   case XLOGYLOG:
   case XLOGYLIN: x=(log(wert/_xmin)/log(_xmax/_xmin));break;
   default:printxy(10,10,"Erst DIAGRAMM Aufrufen!");
  }
  x=x*wwork;
 }
 return (xwork+Math.round(x));
}

function yplot(wert)
{var y=0.0;
 if(_dia)
 {switch(diagrammtyp)
  {case XLINYLIN:
   case XLOGYLIN: y=(wert-_ymin)/(_ymax-_ymin);break;
   case XLINYLOG:
   case XLOGYLOG: y=(log(wert/_ymin)/log(_ymax/_ymin));break;
   default:printxy(10,10,"Erst DIAGRAMM Aufrufen!");
  }
  y=y*hwork;
 }
 return (ywork+hwork-Math.round(y));
}

function xlinskala()
{var wert,x,n,s;
 n=1;
 if(Math.abs(_xmin)>0)n=Math.round(log(Math.abs(_xmin)))+1;    
 if(Math.round(_xstep)==_xstep)n=0;
 wert=_xmin;
 //SetTextJustify(Centertext,Toptext);
 while(wert<=(_xmax+0.01*_xmax))
 {x=xplot(wert);
  draw(x,ywork+hwork,x,ywork,farbe);
  ctx.textBaseline="top";// SetTextJustify(Lefttext,Toptext);
  ctx.textAlign="center";
  printxy(x,ywork+hwork+2,wert);
  wert=wert+_xstep;
 } 
 draw(xwork,ywork+hwork,xwork+wwork,ywork+hwork,farbe);
 ctx.textBaseline="top";
 printxy(xwork+(wwork/2),ywork+hwork+18,xachse);
}

function ylinskala()
{var wert,x,n,s;
 n=1;
 if(Math.abs(_ymin)>0)n=Math.round(log(Math.abs(_ymin)))+1;    
 if(Math.round(_ystep)==_ystep)n=0;
 wert=_ymin;
 while(wert<=(_ymax+0.01*_ymax))
 {y=yplot(wert);
  draw(xwork,y,xwork+wwork,y,farbe);
  ctx.textBaseline="middle"; ctx.textAlign="right";
  printxy(xwork-4,y,wert);
  wert=wert+_ystep;
 } 

 draw(xwork,ywork,xwork,ywork+hwork,farbe);
 ctx.textBaseline="bottom";
 printxy(xwork,ywork+hwork/2,yachse);
}

function xlogskala()
{var wert,pot,tick,x,n,s;
 n=0; 
 if(log(_xmin)<0)n=Math.round(-log(_xmin));    
 pot=Math.round(log(Math.abs(_xmin)));
 while(pot<=log(_xmax))
 {wert=Math.pow(10,pot);
  x=xplot(wert);
  ctx.textBaseline="top";  ctx.textAlign="center";
  printxy(x,ywork+hwork+2,wert);
  for(tick=1;tick<=9;tick++)
  {wert=wert+Math.pow(10,pot);
   x=xplot(wert);
   if(wert<=(_xmax+_xmax*0.01))draw(x,ywork+hwork,x,ywork,farbe);
  }
  pot=pot+1;
 } 
 //printxy(xwork+wwork,ywork+hwork+8,_xmax);
 draw(xwork,ywork+hwork,xwork+wwork,ywork+hwork,farbe);
 ctx.textBaseline="top";
 printxy(xwork+(wwork/2),ywork+hwork+18,xachse);
}

function ylogskala()
{var wert,pot,step,tick,y,n,s;
 n=0; 
 if(log(_ymin)<0)n=Math.round(-log(_ymin));    
 pot=(log(Math.abs(_ymin)));
 while(pot<=log(_ymax))
 {wert=Math.pow(10,pot);
  y=yplot(wert);
  ctx.textBaseline="middle";  ctx.textAlign="right";
  printxy(xwork-4,y,wert);
  for(tick=1;tick<=9;tick++)
  {wert=wert+Math.pow(10,pot);
   y=yplot(wert);
   if(wert<=(_ymax+_ymax*0.01))draw(xwork,y,xwork+wwork,y,farbe);
  }
  pot=pot+1;
 } 
 //printxy(xwork-2,ywork+4,_ymax);
 draw(xwork,ywork,xwork,ywork+hwork,farbe);
 ctx.textBaseline="bottom";
 printxy(xwork,ywork+hwork/2,yachse);
}

function Diagramm(xmin,xmax,xstep,ymin,ymax,ystep)
{if(xstep<=0)xstep=(xmax-xmin)/10.0;
 if(ystep<=0)ystep=(ymax-ymin)/10.0;
 //if (xmax<=xmin) || (ymax<=ymin) BTERROR("Diagramm-Fehler");
 _dia=true;
 _xmin=xmin; _xmax=xmax; _ymin=ymin;
 _ymax=ymax; _xstep=xstep; _ystep=ystep;
  //SetLineStyle(4,$AAAA,1);
  switch(diagrammtyp)
  {case XLINYLIN:   xlinskala(); ylinskala(); break;
   case XLINYLOG:   xlinskala(); ylogskala(); break;
   case XLOGYLIN:   xlogskala(); ylinskala(); break;
   case XLOGYLOG:   xlogskala(); ylogskala(); break;
  }
  //SetLineStyle(0,0,1);
  //SetTextStyle(0,0,0); SetTextJustify(Centertext,Centertext);
  ctx.textAlign="center";
  printxy(xwork+(wwork/2),ywork-12,titel);
}

function Gsin(x){return Math.sin(x/180*Math.PI);}
function Gcos(x){return Math.cos(x/180*Math.PI);}
