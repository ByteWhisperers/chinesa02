import { r as reactExports, j as jsxRuntimeExports, I as Image, y as useNavigate, o as useGameStore, q as useUserInfoStore, ce as getAwardAmount, as as customToFixed, H as Message, a4 as instance, Q as getMoneyUnit, bS as getPlatLaunch, C as Cache, a0 as useWebsetConfig, a1 as useGuideStore, aj as browser } from "./comps-B8ShbmG--2024_12_14_18_4.js";
import { u as useActivityNavigate } from "./util-DT9EqNCx-2024_12_14_18_4.js";
import { s as specialPlatformType } from "./config-B4NCyDU0-2024_12_14_18_4.js";
import { C as CloseIcon, di as DownLoadIcon } from "./icons-Cdaou_E3-2024_12_14_18_4.js";
import { G as Guide } from "./index-DLcyu0vi-2024_12_14_18_4.js";
const svg_theme_fill_color$2 = "_svg_theme_fill_color_1yxjv_3";
const str = "_str_1yxjv_55";
const num = "_num_1yxjv_65";
const css$2 = {
  svg_theme_fill_color: svg_theme_fill_color$2,
  str,
  num
};
const Nums = ({ n, idx, type, isLoading = false }) => {
  const isStr = n === "." || n === ",";
  const strMap = {
    ".": "_s",
    ",": "ss"
  };
  const nus = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
  n = n === "t" ? 10 : n;
  n = isStr ? n : Number(n);
  const [styles, setStyles] = reactExports.useState({
    transitionDelay: "".concat((10 - idx) * 0.01, "s")
  });
  reactExports.useEffect(() => {
    const numEl = document.getElementById("jackpot_number_".concat(idx, "_").concat(n));
    if (numEl && !isLoading) {
      setStyles({
        ...styles,
        transform: "translateY(-".concat(numEl.offsetTop, "px)")
      });
    }
  }, [n, isLoading]);
  return /* @__PURE__ */ jsxRuntimeExports.jsx("div", { style: isStr ? {} : styles, className: isStr ? css$2.str : css$2.num, children: isStr ? /* @__PURE__ */ jsxRuntimeExports.jsx(Image, { src: "/home/jackpot/".concat(strMap[n], "_").concat(type, "_icon.webp") }) : /* @__PURE__ */ jsxRuntimeExports.jsx(jsxRuntimeExports.Fragment, { children: nus.map((nn, key) => {
    return /* @__PURE__ */ jsxRuntimeExports.jsx("span", { id: "jackpot_number_".concat(idx, "_").concat(nn), children: /* @__PURE__ */ jsxRuntimeExports.jsx(Image, { src: "/home/jackpot/".concat(nn, "_").concat(type, "_icon.webp") }) }, key);
  }) }) });
};
const svg_theme_fill_color$1 = "_svg_theme_fill_color_1dse6_3";
const pot_container = "_pot_container_1dse6_55";
const bg = "_bg_1dse6_63";
const bg_1 = "_bg_1_1dse6_73";
const bg_2 = "_bg_2_1dse6_77";
const bg_3 = "_bg_3_1dse6_81";
const bg_4 = "_bg_4_1dse6_85";
const numsBox = "_numsBox_1dse6_89";
const numsBox_1 = "_numsBox_1_1dse6_98";
const numsBox_2 = "_numsBox_2_1dse6_102";
const numsBox_3 = "_numsBox_3_1dse6_106";
const numsBox_4 = "_numsBox_4_1dse6_110";
const css$1 = {
  svg_theme_fill_color: svg_theme_fill_color$1,
  pot_container,
  bg,
  bg_1,
  bg_2,
  bg_3,
  bg_4,
  numsBox,
  numsBox_1,
  numsBox_2,
  numsBox_3,
  numsBox_4
};
const BGComp = ({ type = 1, children, url, onClick, className }) => {
  return /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: "".concat(css$1.pot_container, " ").concat(className), onClick, children: [
    /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: "".concat(css$1.bg, " ").concat(css$1.bg_1, " ").concat(css$1["bg_" + type]), children: /* @__PURE__ */ jsxRuntimeExports.jsx(
      Image,
      {
        isGame: type == 5,
        remote: type == 5,
        src: type == 5 ? url : "/home/jackpot/jackpot_bg_".concat(type, ".webp")
      }
    ) }),
    /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: "".concat(css$1.numsBox, " ").concat(css$1.numsBox_1, " ").concat(css$1["numsBox_" + type]), children })
  ] });
};
const format = ["ttt.ttt.ttt", "ttt.ttt.ttt,t", "ttt.ttt.ttt,tt"];
const formatAmount = (num2, decimalLen) => {
  return format[decimalLen].split("").reverse().map((n, i) => {
    return num2.split("").reverse()[i] || (n === "t" ? 0 : n);
  }).reverse();
};
const Jackpot = ({ className }) => {
  const navigate = useNavigate();
  const { switchPage } = useActivityNavigate();
  const { games, gameOpenMap, activityList } = useGameStore();
  const { award, websetConfig, token, info } = useUserInfoStore();
  const [amount, setAmount] = reactExports.useState(0);
  const [amountSplit, setAmountSplit] = reactExports.useState("ttt.ttt.ttt,tt".split(""));
  reactExports.useEffect(() => {
    getAwardAmount().then(([res]) => {
      if (res) {
        setAmount(res.amount);
        useUserInfoStore.setState({ prefix: res.prefix });
      }
    });
  }, []);
  reactExports.useEffect(() => {
    if (award) {
      setAmount(award);
    }
  }, [award]);
  reactExports.useEffect(() => {
    var _a;
    const decimalPlaces = String((_a = websetConfig.decimalPlaces) != null ? _a : 2);
    const num2 = customToFixed((amount || 0) / 100, decimalPlaces);
    setAmountSplit(formatAmount(num2, decimalPlaces));
  }, [amount, websetConfig.decimalPlaces]);
  const getPlatLaunchFun = async (id, min_admission) => {
    if (!token) {
      useUserInfoStore.setState({ openForLogin: true });
      return;
    }
    if (info && +info.balance < (+min_admission || 0)) {
      Message.error(instance.t("您的余额低于「最低准入金额」，请存款后才能玩", { amount: min_admission, coin: getMoneyUnit(true) }));
      return;
    }
    const [res, error] = await getPlatLaunch({ id }, { useLoading: true });
    if (res) {
      Cache.set("gameStart", res);
      navigate("/gameStart");
    }
  };
  const runToPage = () => {
    if (websetConfig.pool_forward_jump_type == "1") {
      const activity = activityList.find(
        (a) => a.id === websetConfig.pool_forward_id
      );
      if (activity) {
        switchPage({
          flag: websetConfig.pool_forward_flag,
          id: websetConfig.pool_forward_id
        });
      }
    } else if (websetConfig.pool_forward_jump_type == "2") {
      const g = games.find(
        (item) => item.id == websetConfig.pool_forward_game_type
      );
      if (g && gameOpenMap[g.name]) {
        const gl = g.l.find((l) => l.id === websetConfig.pool_forward_id);
        if (gl) {
          if (specialPlatformType.includes(g.name)) {
            getPlatLaunchFun(gl.id, gl.min_admission);
            return;
          }
          navigate("/venuegame?&id=".concat(gl.game_type, "&pid=").concat(gl.id || "0"));
        }
      }
    }
  };
  if (websetConfig.pool_switch != "1") return null;
  return /* @__PURE__ */ jsxRuntimeExports.jsx(
    BGComp,
    {
      type: websetConfig.pool_style || 1,
      url: websetConfig.pool_custom_style,
      onClick: runToPage,
      className,
      children: amountSplit.map((n, idx) => {
        return /* @__PURE__ */ jsxRuntimeExports.jsx(
          Nums,
          {
            n,
            idx,
            type: websetConfig.pool_money_style || 1
          },
          idx
        );
      })
    }
  );
};
const svg_theme_fill_color = "_svg_theme_fill_color_8jr3q_3";
const downloadbg = "_downloadbg_8jr3q_55";
const download = "_download_8jr3q_55";
const closeIcon = "_closeIcon_8jr3q_115";
const downloadIcon = "_downloadIcon_8jr3q_129";
const btnIcon = "_btnIcon_8jr3q_132";
const download2 = "_download2_8jr3q_143";
const title = "_title_8jr3q_146";
const loadIcon = "_loadIcon_8jr3q_151";
const css = {
  svg_theme_fill_color,
  downloadbg,
  download,
  closeIcon,
  downloadIcon,
  btnIcon,
  download2,
  title,
  loadIcon
};

const Download = () => {
  const { websetConfig } = useWebsetConfig();
  const { standalone } = useGuideStore();
  const {
    appUrl,
    closeAppDownloadTopTips
  } = useUserInfoStore();
  const guideRef = reactExports.useRef();

  // --- Deixa apenas "origin + pathname" (remove ?query e #hash) ---
  const toPureUrl = (u) => {
    try {
      const url = new URL(String(u).trim(), window.location.origin);
      return `${url.origin}${url.pathname}`;
    } catch {
      // fallback se "u" for relativo ou inválido
      return String(u).split("#")[0].split("?")[0];
    }
  };

  // Detecta plataforma (ios/android/web)
  const detectPlatform = () => {
    try {
      if (typeof browser !== "undefined" && browser.versions) {
        if (browser.versions.ios) return "ios";
        if (browser.versions.android) return "android";
      }
    } catch (e) {}
    const ua = typeof navigator !== "undefined" ? navigator.userAgent : "";
    const isAndroid = /Android/i.test(ua);
    const isIOS =
      /iPad|iPhone|iPod/i.test(ua) ||
      ((/Macintosh|Mac OS X/i.test(ua)) && typeof window !== "undefined" && "ontouchend" in window);
    if (isIOS) return "ios";
    if (isAndroid) return "android";
    return "web";
  };

  // Busca o link na /app/get_link (tenta POST; se falhar, tenta GET)
  const fetchAppLink = async (platform) => {
    // 1) POST JSON
    try {
      const res = await fetch("/app/get_link", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ platform })
      });
      if (res.ok) {
        const data = await res.json().catch(() => ({}));
        if (data?.ok && data?.url) return data.url;
        if (typeof data === "string" && /^https?:\/\//i.test(data)) return data;
      }
    } catch {}
    // 2) GET com querystring
    try {
      const res2 = await fetch(`/app/get_link?platform=${encodeURIComponent(platform)}`, { method: "GET" });
      if (res2.ok) {
        const text = (await res2.text()).trim();
        try {
          const json = JSON.parse(text);
          if (json?.ok && json?.url) return json.url;
        } catch {
          if (/^https?:\/\//i.test(text)) return text;
        }
      }
    } catch {}
    throw new Error("Falha ao obter link da /app/get_link");
  };

  // Sempre busca na /app/get_link e abre a URL pura (sem query/hash)
  const downloadFn = async () => {
    const preOpen = window.open("", "_blank"); // evita bloqueio de popup
    try {
      const platform = detectPlatform();
      const raw = await fetchAppLink(platform);
      const pure = toPureUrl(raw);
      if (preOpen && !preOpen.closed) {
        preOpen.location.href = pure;
      } else {
        const ok = window.open(pure, "_blank");
        if (!ok) window.location.href = pure;
      }
    } catch {
      const pureFallback = toPureUrl(appUrl || "/");
      if (preOpen && !preOpen.closed) {
        preOpen.location.href = pureFallback;
      } else {
        const ok = window.open(pureFallback, "_blank");
        if (!ok) window.location.href = pureFallback;
      }
    }
  };

  return /* @__PURE__ */ jsxRuntimeExports.jsxs(jsxRuntimeExports.Fragment, { children: [
    websetConfig.banner_switch != "0" && !standalone && /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.download, id: "downloadDom", children: [
      /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { children: [
        /* @__PURE__ */ jsxRuntimeExports.jsx("span", { onClick: () => {
          closeAppDownloadTopTips();
        }, children: /* @__PURE__ */ jsxRuntimeExports.jsx(CloseIcon, { className: css.closeIcon }) }),
        websetConfig.banner_img ? /* @__PURE__ */ jsxRuntimeExports.jsx(
          Image,
          {
            src: websetConfig.banner_img,
            remote: true,
            isGame: true,
            className: css.downloadIcon
          }
        ) : /* @__PURE__ */ jsxRuntimeExports.jsx(
          Image,
          {
            src: "/xxxx/h5/download1.webp",
            className: css.downloadIcon
          }
        )
      ] }),
      /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: css.download2, onClick: () => {
        // sempre busca /app/get_link e abre o link PURO
        downloadFn();
      }, children: /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.btnIcon, children: [
        /* @__PURE__ */ jsxRuntimeExports.jsx(DownLoadIcon, { className: css.loadIcon }),
        /* @__PURE__ */ jsxRuntimeExports.jsx("span", { className: css.title, children: instance.t("Baixar App") })
      ] }) })
    ] }),
    /* @__PURE__ */ jsxRuntimeExports.jsx(Guide, { openPop: false, openGuide: false, event: guideRef })
  ] });
};
export {
  Download as D,
  Jackpot as J
};
