/* FlyEnv Toolbox — i18n Translation System */
const I18N = {
  zh: {
    cat_code:'代码',cat_development:'开发',cat_crypto:'加密',
    cat_converter:'转换',cat_web:'网络',cat_images:'图片',
    section_fav:'☆ 收藏',section_all:'全部工具',
    search_ph:'搜索工具...',tool_ph:'工具',
    status_ready:'就绪',lang_en:'EN',lang_zh:'中',
    ori:'原始:',mod:'修改后:',cmp:'比较',swp:'交换',sam:'示例',
    fmt:'格式化',min:'压缩',srt:'排序',val:'验证',
    cal:'计算',preset:'预设',
    dec:'解码',enc:'编码',algo:'算法',secret:'密钥',payload:'载荷',token_ph:'令牌...',
    hash_lbl:'输入文本:',
    key:'密钥',pass:'密码',cenc:'加密',cdec:'解密',
    ts2d:'转日期',ts2t:'转时间戳',now:'现在',
    b64enc:'编码',b64dec:'解码',
    urlenc:'URL 编码',urldec:'URL 解码',urlpar:'解析',
    esc:'转义',unesc:'反转义',strip:'剥离标签',
    pat:'模式',flags:'标记',sub:'文本',
    rmatch:'匹配',rall:'全部',rrep:'替换',rspl:'分割',
    owner:'所有者',group:'用户组',others:'其他',
    type:'类型',len:'长度',cnt:'数量',gen:'生成',
    http_search:'搜索状态码...',
    ext:'扩展名',lookup:'查找',
    det:'检测',cln:'清理',
    prev:'预览',
    conn:'连接',disc:'断开',send:'发送',msg_ph:'消息...',
    run:'运行',copy:'复制',
    path:'路径',quality:'质量',maxw:'最大宽度',compress:'压缩',
    full:'全屏',area:'区域',win:'窗口',
    bits:'位数',domain:'域名',days:'天数',
    info:'信息',test:'测试',fetch:'抓取',obf:'混淆',
    gen_text:'文本 / URL:',
    fold:'折叠',
    portkill:'端口查杀',pk_ph:'输入端口号...',pk_kill_sel:'结束选中',pk_kill_all:'结束全部',
    prockill:'进程查杀',proc_ph:'输入进程关键词...',
    pk_none:'未查询到信息',pk_processes:'进程列表',pk_no_sel:'请先选择进程',pk_no_res:'无结果',
    kc_hint:'在此按下任意键',kc_key:'按键',kc_code:'代码',kc_loc:'位置',kc_mod:'修饰键',kc_clr:'清除',kc_his:'历史',
  },
  en: {
    cat_code:'Code',cat_development:'Development',cat_crypto:'Crypto',
    cat_converter:'Converter',cat_web:'Web',cat_images:'Images',
    section_fav:'☆ Favorites',section_all:'All Tools',
    search_ph:'Search tools...',tool_ph:'Tool',
    status_ready:'Ready',lang_en:'EN',lang_zh:'中',
    ori:'Original:',mod:'Modified:',cmp:'Compare',swp:'Swap',sam:'Sample',
    fmt:'Format',min:'Minify',srt:'Sort',val:'Validate',
    cal:'Calculate',preset:'Presets',
    dec:'Decode',enc:'Encode',algo:'Algo',secret:'Secret',payload:'Payload',token_ph:'Token...',
    hash_lbl:'Input text:',
    key:'Key',pass:'Password',cenc:'Encrypt',cdec:'Decrypt',
    ts2d:'To Date',ts2t:'To TS',now:'Now',
    b64enc:'Encode',b64dec:'Decode',
    urlenc:'Encode',urldec:'Decode',urlpar:'Parse',
    esc:'Escape',unesc:'Unescape',strip:'Strip',
    pat:'Pattern',flags:'Flags',sub:'Subject',
    rmatch:'Match',rall:'All',rrep:'Replace',rspl:'Split',
    owner:'Owner',group:'Group',others:'Others',
    type:'Type',len:'Length',cnt:'Count',gen:'Generate',
    http_search:'Search status...',
    ext:'Extension',lookup:'Lookup',
    det:'Detect',cln:'Clean',
    prev:'Preview',
    conn:'Connect',disc:'Disconnect',send:'Send',msg_ph:'Message...',
    run:'Run',copy:'Copy',
    path:'Path',quality:'Quality',maxw:'Max W',compress:'Compress',
    full:'Full',area:'Area',win:'Window',
    bits:'Bits',domain:'CN',days:'Days',
    info:'Info',test:'Test',fetch:'Fetch',obf:'Obfuscate',
    gen_text:'Text / URL:',
    fold:'Fold',
    portkill:'Port Kill',pk_ph:'Input port...',pk_kill_sel:'Kill Selected',pk_kill_all:'Kill All',
    prockill:'Process Kill',proc_ph:'Input process keyword...',
    pk_none:'No info found',pk_processes:'Processes',pk_no_sel:'Select processes first',pk_no_res:'No results',
    kc_hint:'Press any key here',kc_key:'Key',kc_code:'Code',kc_loc:'Location',kc_mod:'Modifiers',kc_clr:'Clear',kc_his:'History',
  }
};

let lang = localStorage.getItem('flyenv_lang') || 'zh';
function _t(k){return (I18N[lang]&&I18N[lang][k])||(I18N.en[k])||k;}

function toggleLang(){
  lang=lang==='zh'?'en':'zh';
  localStorage.setItem('flyenv_lang',lang);
  document.getElementById('langBtn').textContent=lang==='zh'?'EN':'中';
  // 全局刷新：清除缓存，重新渲染整个界面
  document.getElementById('searchInput').placeholder=_t('search_ph');
  // Remove all cached panels
  if (typeof panelCache !== 'undefined') {
    Object.keys(panelCache).forEach(function(k) {
      var el = panelCache[k];
      if (el && el.parentNode) el.parentNode.removeChild(el);
    });
    panelCache = {};
  }
  if(curTool){
    openTool(curTool);
  }else{
    renderHome();
  }
}
