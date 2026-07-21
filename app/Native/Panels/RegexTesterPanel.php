<?php

declare(strict_types=1);

namespace App\Native\Panels;

use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\WebViewSpec;
use Yangweijie\Ui2\Widgets\Surface;

final class RegexTesterPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $webId = "{$key}:web";
        $leaf = LayoutNode::leaf($webId, new WebViewSpec(html: self::initialHtml()), width: $width, height: $height);
        $leaf->style->grow = 1.0;

        return $leaf;
    }

    private static function initialHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root{
  --el-color-primary:#409eff;--el-color-danger:#f56c6c;--el-color-warning:#e6a23c;--el-color-success:#67c23a;
  --el-text-color-primary:#303133;--el-text-color-regular:#606266;--el-text-color-secondary:#909399;--el-text-color-placeholder:#a8abb2;
  --el-border-color:#dcdfe6;--el-border-color-light:#e4e7ed;
  --el-fill-color:#f0f2f5;--el-fill-color-blank:#fff;--el-bg-color:#fff;--el-bg-color-page:#f2f3f5;
  --el-border-radius-base:4px;--el-font-size-base:13px;
  --el-font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'PingFang SC','Microsoft YaHei',sans-serif;
  --bg:#f5f7fa;--sf:#fff;--bd:#e4e7ed;--tx:#303133;
}
body.dark{
  --el-text-color-primary:#e5eaf3;--el-text-color-regular:#cfd3dc;--el-text-color-secondary:#a3a6ad;--el-text-color-placeholder:#8d9095;
  --el-border-color:#4c4d4f;--el-border-color-light:#414243;--el-fill-color:#262727;--el-fill-color-blank:#1d1e1f;
  --el-bg-color:#1d1e1f;--el-bg-color-page:#141414;
  --bg:#1a1a2e;--sf:#16213e;--bd:#2a2a4a;--tx:#e4e6ef;
}
*{box-sizing:border-box}html,body{margin:0;padding:0;height:100%}
body{font-family:var(--el-font-family);font-size:var(--el-font-size-base);color:var(--el-text-color-primary);background:var(--bg);overflow:auto}
.tool-main{padding:16px}
.ep-card{background:var(--el-bg-color);border:1px solid var(--el-border-color);border-radius:8px;padding:16px;margin-bottom:12px}
.ep-card__header{font-weight:600;font-size:15px;margin-bottom:12px}
.ep-form-item{margin-bottom:12px}
.ep-form-item>label{display:block;font-size:13px;color:var(--el-text-color-regular);margin-bottom:6px}
.ep-input,.ep-textarea{width:100%;border:1px solid var(--el-border-color);border-radius:var(--el-border-radius-base);padding:7px 10px;font-size:13px;font-family:inherit;background:var(--el-fill-color-blank);color:var(--el-text-color-primary);outline:none}
.ep-input:focus,.ep-textarea:focus{border-color:var(--el-color-primary)}
.ep-textarea{resize:vertical;min-height:60px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.ep-flags{display:flex;flex-wrap:wrap;gap:4px 14px;margin:6px 0}
.ep-check{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;color:var(--el-text-color-regular)}
.ep-check input[type="checkbox"]{accent-color:var(--el-color-primary)}
.ep-check .flag-name{color:var(--el-color-primary)}
.ep-alert{border-radius:var(--el-border-radius-base);padding:10px 12px;font-size:13px;margin-bottom:10px}
.ep-alert--warning{background:rgba(230,162,60,.12);color:var(--el-color-warning)}
.ep-alert--danger{background:rgba(245,108,108,.12);color:var(--el-color-danger)}
.ep-alert--success{background:rgba(103,194,58,.12);color:var(--el-color-success)}
.ep-table{width:100%;border-collapse:collapse;font-size:13px}
.ep-table th,.ep-table td{border:1px solid var(--el-border-color);padding:6px 10px;text-align:left;vertical-align:top}
.ep-table th{background:var(--el-fill-color);font-weight:600}
.ep-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
.match-highlight{background:rgba(64,158,255,.2);border-bottom:2px solid var(--el-color-primary);padding:1px 0}
.group-highlight{background:rgba(103,194,58,.2);border-bottom:2px solid var(--el-color-success);padding:1px 0}
.diagram-svg{width:100%;min-height:60px}
</style></head>
<body class="dark"><div class="tool-main">

<div class="ep-card">
  <div class="ep-card__header">Regex</div>
  <div class="ep-form-item"><label>Regex to test:</label>
    <textarea id="rxP" class="ep-textarea" rows="3" placeholder="Put the regex to test" oninput="rxCompute()"></textarea>
  </div>
  <div class="ep-flags">
    <label class="ep-check"><input type="checkbox" id="rx_g" checked onchange="rxCompute()"> <span class="flag-name">Global Search [g]</span></label>
    <label class="ep-check"><input type="checkbox" id="rx_i" onchange="rxCompute()"> <span class="flag-name">Case-Insensitive Search [i]</span></label>
    <label class="ep-check"><input type="checkbox" id="rx_m" onchange="rxCompute()"> <span class="flag-name">Multiline [m]</span></label>
    <label class="ep-check"><input type="checkbox" id="rx_s" checked onchange="rxCompute()"> <span class="flag-name">Single Line [s]</span></label>
    <label class="ep-check"><input type="checkbox" id="rx_u" onchange="rxCompute()"> <span class="flag-name">Unicode [u]</span></label>
    <label class="ep-check"><input type="checkbox" id="rx_v" onchange="rxCompute()"> <span class="flag-name">Unicode Sets [v]</span></label>
  </div>
  <div class="ep-form-item"><label>Text to match:</label>
    <textarea id="rxT" class="ep-textarea" rows="5" placeholder="Put the text to match" oninput="rxCompute()"></textarea>
  </div>
</div>

<div class="ep-card">
  <div class="ep-card__header">Matches</div>
  <div id="rxMatches"></div>
</div>

<div class="ep-card">
  <div class="ep-card__header">Sample matching text</div>
  <div id="rxSample"></div>
</div>

<div class="ep-card">
  <div class="ep-card__header">Regex Diagram</div>
  <div id="rxDiagram"><svg class="diagram-svg" id="rxDiagramSvg"></svg></div>
</div>

<script>
function g(id){return document.getElementById(id);}
function esc(s){return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function rxMatch(regex,text,flags){
  try{
    var re=new RegExp(regex,flags);
    var results=[];var lastIndex=-1;var m=re.exec(text);
    while(m!==null){
      if(re.lastIndex===lastIndex||m[0]==='')break;
      var caps=[];
      for(var i=1;i<m.length;i++){
        if(m[i]!==undefined){
          var start=-1,end=-1;
          if(m.indices&&m.indices[i]){start=m.indices[i][0];end=m.indices[i][1];}
          caps.push({index:i,value:m[i],start:start,end:end});
        }
      }
      var groups=[];
      if(m.groups){
        for(var k in m.groups){
          if(m.groups[k]!==undefined){
            var gs=-1,ge=-1;
            if(m.indices&&m.indices.groups&&m.indices.groups[k]){gs=m.indices.groups[k][0];ge=m.indices.groups[k][1];}
            groups.push({name:k,value:m.groups[k],start:gs,end:ge});
          }
        }
      }
      results.push({index:m.index,value:m[0],captures:caps,groups:groups});
      lastIndex=re.lastIndex;m=re.exec(text);
    }
    return {matches:results,error:null};
  }catch(e){
    return {matches:[],error:e.message};
  }
}

function rxCompute(){
  var p=g('rxP').value,t=g('rxT').value;
  var outM=g('rxMatches'),outS=g('rxSample'),outD=g('rxDiagram');

  if(!p){
    outM.innerHTML='<div class="ep-alert ep-alert--warning">Enter a regular expression to begin matching</div>';
    outS.innerHTML='<div class="muted">Enter a regex and text to see sample matches</div>';
    outD.innerHTML='<svg class="diagram-svg" id="rxDiagramSvg"></svg>';
    return;
  }

  var flags='d';
  if(g('rx_g').checked)flags+='g';
  if(g('rx_i').checked)flags+='i';
  if(g('rx_m').checked)flags+='m';
  if(g('rx_s').checked)flags+='s';
  if(g('rx_u').checked)flags+='u';else if(g('rx_v').checked)flags+='v';

  var result=rxMatch(p,t,flags);

  if(result.error){
    outM.innerHTML='<div class="ep-alert ep-alert--danger">Invalid regex: '+esc(result.error)+'</div>';
    outS.innerHTML='';
    outD.innerHTML='<svg class="diagram-svg" id="rxDiagramSvg"></svg>';
    return;
  }

  if(!result.matches.length){
    outM.innerHTML='<div class="ep-alert ep-alert--warning">No match</div>';
    outS.innerHTML='<div class="muted">No matches found</div>';
  }else{
    var rows=result.matches.map(function(r,i){
      var capsStr=r.captures.length?r.captures.map(function(c){
        return '<span class="ep-mono">Group '+(c.index)+': '+esc(c.value)+(c.start>=0?' ['+c.start+'-'+c.end+']':'')+'</span>';
      }).join('<br>'):'<span class="muted">-</span>';
      var grpsStr=r.groups.length?r.groups.map(function(gr){
        return '<span class="ep-mono">"'+esc(gr.name)+'": '+esc(gr.value)+(gr.start>=0?' ['+gr.start+'-'+gr.end+']':'')+'</span>';
      }).join('<br>'):'<span class="muted">-</span>';
      return '<tr><td>'+(i+1)+'</td><td class="ep-mono">'+esc(r.value)+'</td><td>'+r.index+'</td><td>'+capsStr+'</td><td>'+grpsStr+'</td></tr>';
    });
    outM.innerHTML='<table class="ep-table"><thead><tr><th>#</th><th>Match</th><th>Index</th><th>Capture Groups</th><th>Named Groups</th></tr></thead><tbody>'+rows.join('')+'</tbody></table>';
  }

  // Sample matching text — highlight matches in original text
  if(t&&result.matches.length){
    var highlighted=t;
    var offset=0;
    var sorted=result.matches.slice().sort(function(a,b){return a.index-b.index;});
    var parts=[];var lastIdx=0;
    sorted.forEach(function(r){
      if(r.index>lastIdx)parts.push(esc(t.substring(lastIdx,r.index)));
      parts.push('<span class="match-highlight">'+esc(t.substring(r.index,r.index+r.value.length))+'</span>');
      lastIdx=r.index+r.value.length;
    });
    if(lastIdx<t.length)parts.push(esc(t.substring(lastIdx)));
    outS.innerHTML='<div class="ep-mono" style="white-space:pre-wrap;line-height:1.6">'+parts.join('')+'</div>';
  }else{
    outS.innerHTML='<div class="muted">Enter text to see highlighted matches</div>';
  }

  // Regex Diagram — simple ASCII-style tree
  drawDiagram(p, outD);
}

function drawDiagram(pattern, container){
  if(!pattern){container.innerHTML='<svg class="diagram-svg" id="rxDiagramSvg"></svg>';return;}

  var nodes=[];var x=20;var y=30;var level=0;
  var chars=pattern.split('');
  var i=0;

  // Simple tokenizer for diagram
  while(i<chars.length){
    var ch=chars[i];
    if(ch==='\\'&&i+1<chars.length){
      var next=chars[i+1];
      var label='\\'+next;
      if(next==='d'||next==='D')label+=' (digit)';
      else if(next==='w'||next==='W')label+=' (word)';
      else if(next==='s'||next==='S')label+=' (whitespace)';
      else if(next==='b')label+=' (boundary)';
      nodes.push({x:x,label:label,type:'token'});
      x+=Math.max(label.length*8+20,60);
      i+=2;
    }else if(ch==='['){
      var end=pattern.indexOf(']',i);
      if(end>0){
        var set=pattern.substring(i,end+1);
        nodes.push({x:x,label:set,type:'set'});
        x+=Math.max(set.length*7+20,60);
        i=end+1;
      }else{
        nodes.push({x:x,label:ch,type:'char'});
        x+=30;i++;
      }
    }else if(ch==='('){
      nodes.push({x:x,label:'group',type:'group-start'});
      x+=50;i++;
    }else if(ch===')'){
      nodes.push({x:x,label:')',type:'group-end'});
      x+=30;i++;
    }else if(ch==='*'||ch==='+'||ch==='?'){
      nodes.push({x:x,label:ch,type:'quantifier'});
      x+=30;i++;
    }else if(ch==='{'){
      var end=pattern.indexOf('}',i);
      if(end>0){
        var q=pattern.substring(i,end+1);
        nodes.push({x:x,label:q,type:'quantifier'});
        x+=Math.max(q.length*8+20,50);
        i=end+1;
      }else{
        nodes.push({x:x,label:ch,type:'char'});
        x+=30;i++;
      }
    }else if(ch==='^'||ch==='$'){
      nodes.push({x:x,label:ch,type:'anchor'});
      x+=30;i++;
    }else if(ch==='|'){
      nodes.push({x:x,label:'OR',type:'alternate'});
      x+=40;i++;
    }else{
      nodes.push({x:x,label:ch,type:'char'});
      x+=25;i++;
    }
  }

  var svgW=Math.max(x+20,200);
  var svgH=80;
  var svg='<svg xmlns="http://www.w3.org/2000/svg" width="'+svgW+'" height="'+svgH+'" style="width:100%;min-height:'+svgH+'px">';

  // Draw connections
  nodes.forEach(function(n,i){
    if(i>0){
      var prev=nodes[i-1];
      svg+='<line x1="'+(prev.x+prev.label.length*4)+'" y1="'+y+'" x2="'+n.x+'" y2="'+y+'" stroke="#666" stroke-width="1.5"/>';
    }
  });

  // Draw nodes
  nodes.forEach(function(n){
    var color='#409eff';
    if(n.type==='quantifier')color='#e6a23c';
    else if(n.type==='set')color='#67c23a';
    else if(n.type==='anchor')color='#f56c6c';
    else if(n.type==='group-start'||n.type==='group-end')color='#909399';
    else if(n.type==='alternate')color='#f56c6c';

    var w=n.label.length*7+12;
    svg+='<rect x="'+(n.x-2)+'" y="'+(y-16)+'" width="'+w+'" height="22" rx="4" fill="'+color+'" opacity="0.15" stroke="'+color+'" stroke-width="1"/>';
    svg+='<text x="'+(n.x+w/2-2)+'" y="'+(y-1)+'" text-anchor="middle" font-size="11" font-family="Menlo,monospace" fill="'+color+'">'+esc(n.label)+'</text>';
  });

  svg+='</svg>';
  container.innerHTML=svg;
}

rxCompute();
</script>
</body></html>
HTML;
    }
}
