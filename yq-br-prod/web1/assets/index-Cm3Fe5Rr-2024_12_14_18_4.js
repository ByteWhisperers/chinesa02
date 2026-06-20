import { 
  r as reactExports, 
  y as useNavigate, 
  j as jsxRuntimeExports, 
  I as Image, 
  a6 as desktopOpen, 
  q as useUserInfoStore, 
  a9 as useReactive, 
  u as useAsyncEffect, 
  az as getBanner, 
  aa as minPxChip 
} from "./comps-B8ShbmG--2024_12_14_18_4.js";

/* ====== CSS importado do bundle ====== */
const carouselBox = "_carouselBox_ygd8w_55";
const contentBox = "_contentBox_ygd8w_63";
const points = "_points_ygd8w_91";
const point_box = "_point_box_ygd8w_99";
const point = "_point_ygd8w_91";
const active = "_active_ygd8w_120";

const css$1 = { carouselBox, contentBox, points, point_box, point, active };
const bannerItemBox = "_bannerItemBox_11bva_55";
const middleImg = "_middleImg_11bva_71";
const css = { bannerItemBox, middleImg };

/* ====== TOQUE ====== */
const cacheState$1 = { startClientX: 0, endClientX: 0 };

/* ====== ITEM (cada slide) ====== */
const Item = (props = {}) => {
  const { item, imgs, index, updateSelectIndex } = props;
  const lastImg2 = imgs[index - 1] || null;
  const nextImg2 = imgs[index + 1] || null;
  const dom = reactExports.useRef({});
  const navigate = useNavigate();

  reactExports.useEffect(() => {
    if (dom.current) {
      const onTouchStart = (e) => {
        const touch = e.targetTouches[0] || {};
        cacheState$1.startClientX = touch.clientX || 0;
      };
      dom.current.addEventListener("touchstart", onTouchStart, { passive: true });

      const onTouchend = (e) => {
        const touch = e.changedTouches[0] || {};
        cacheState$1.endClientX = touch.clientX || 0;
        const cha = cacheState$1.endClientX - cacheState$1.startClientX;
        if (cha > 40 && lastImg2) updateSelectIndex(index - 1);
        if (cha < -40 && nextImg2) updateSelectIndex(index + 1);
      };
      dom.current.addEventListener("touchend", onTouchend, { passive: true });

      return () => {
        if (!dom.current) return;
        dom.current.removeEventListener("touchstart", onTouchStart);
        dom.current.removeEventListener("touchend", onTouchend);
      };
    }
  }, [dom.current]);

  return jsxRuntimeExports.jsx("div", {
    ref: dom,
    className: css.bannerItemBox,
    children: jsxRuntimeExports.jsx(Image, {
      remote: true,
      isGame: true,
      src: item.images,
      className: css.middleImg,
      onClick: () => {
        if (item.flags === "1") navigate(item.url);
        if (item.flags === "2") desktopOpen(item.url);
      }
    })
  });
};

/* ====== CATEGORIAS (acima do carrossel) ====== */
const CategoriesRail = ({ items }) => {
  const navigate = useNavigate();
  return jsxRuntimeExports.jsx("div", {
    style: {
      display: "flex",
      justifyContent: "space-around",
      alignItems: "center",
      padding: "0",
marginBottom: "0px",
      background: "transparent" // ou transparente, se quiser
    },
    children: items.map((c) =>
      jsxRuntimeExports.jsxs(
        "button",
        {
          style: {
            display: "flex",
            flexDirection: "column",
            alignItems: "center",
            gap: "6px",
            background: "none",
            border: "none",
            cursor: "pointer"
          },
          onClick: () => (c.url ? navigate(c.url) : null),
          children: [
            jsxRuntimeExports.jsx("img", {
              src: c.img,
              alt: c.label,
              style: {width: "72px", height: "72px", objectFit: "contain" }
            }),
            jsxRuntimeExports.jsx("span", { style: { fontSize: "12px" }, children: c.label })
          ]
        },
        c.id
      )
    )
  });
};

/* ====== ESTADO DO CAROUSEL ====== */
const cacheState = { timer: null, timeoutTimer: null };

const Carousel = () => {
  const { language } = useUserInfoStore();
  const boxDom = reactExports.useRef();
  const state = useReactive({ selectIdx: 0, imgs: [], min: 0, max: 0 });
  const { imgs } = state;

  const checkoutPlayBack = () => {
    clearTimeout(cacheState.timeoutTimer);
    cacheState.timeoutTimer = setTimeout(() => {
      if (state.selectIdx > state.max) {
        if (boxDom.current) boxDom.current.style.transition = "all 0s ease-in-out";
        state.selectIdx = state.min;
        setTimeout(() => {
          if (boxDom.current) boxDom.current.style.transition = "all 0.2s ease-in-out";
        }, 200);
      }
      if (state.selectIdx < state.min) {
        if (boxDom.current) boxDom.current.style.transition = "all 0s ease-in-out";
        state.selectIdx = state.max;
        setTimeout(() => {
          if (boxDom.current) boxDom.current.style.transition = "all 0.2s ease-in-out";
        }, 200);
      }
    }, 300);
  };

  const updateSelectIndex = (index) => {
    if (index === state.selectIdx) return;
    if (imgs.length < 2) return;
    clearTimer();
    state.selectIdx = index;
    checkoutPlayBack();
    startTimer();
  };

  useAsyncEffect(async () => {
    if (imgs.length === 0) {
      clearTimer();
      const [res] = await getBanner({ flags: 2 });
      if (res && res.length > 0) {
        state.imgs = res;
        if (res.length > 0) {
          state.selectIdx = res.length;
          state.min = res.length;
          state.max = res.length * 2 - 1;
        }
        startTimer();
      }
    }
  }, [imgs]);

  reactExports.useEffect(() => () => clearTimer(), []);

  const clearTimer = () => clearInterval(cacheState.timer);
  const startTimer = () => {
    cacheState.timer = setInterval(() => {
      state.selectIdx = state.selectIdx + 1;
      checkoutPlayBack();
    }, 4500);
  };

  const { selectIdx } = state;
  const windowWidth = minPxChip() * 750;

  /* categorias */
  const categories = [
    { id: "promo",  img: "https://i.postimg.cc/ZY1wwFJQ/Design-sem-nome-7.png", url: "/activity" },
    { id: "recomp", img: "https://i.postimg.cc/m2mJ8161/Design-sem-nome-20.png", url: "/missao" },
    { id: "juros", img: "https://i.postimg.cc/J7fQNN8z/Design-sem-nome-21.png", url: "/central-wallet" },
    { id: "fundo",  img: "https://i.postimg.cc/z3mBMDj9/Design-sem-nome-22.png", url: "/alreadyGetbonus" },
    { id: "rebate", img: "https://i.postimg.cc/TYGhr1KD/Design-sem-nome-23.png", url: "/back-rate" },
    { id: "tarefa",  img: "https://i.postimg.cc/T117Sfwq/Design-sem-nome-24.png", url: "/missao" }
  ];

  return jsxRuntimeExports.jsxs("div", {
    children: [
      jsxRuntimeExports.jsx(CategoriesRail, { items: categories }),

      jsxRuntimeExports.jsxs("div", {
        className: css$1.carouselBox,
        style: { marginTop: "-8px" }, 
        children: [
          jsxRuntimeExports.jsx("div", {
            ref: boxDom,
            style: {
              
              transform: `translateX(${language == "ur" ? 1 * selectIdx * windowWidth : -1 * selectIdx * windowWidth}px)`
            },
            className: css$1.contentBox,
            children: [...imgs, ...imgs, ...imgs].map((item, idx) =>
              jsxRuntimeExports.jsx(
                Item,
                {
                  updateSelectIndex: (idx2) => updateSelectIndex(idx2),
                  index: idx,
                  item,
                  imgs: [...imgs, ...imgs, ...imgs]
                },
                idx
              )
            )
          }),

          jsxRuntimeExports.jsx("div", {
            className: css$1.points,
            children: jsxRuntimeExports.jsx("div", {
              className: css$1.point_box,
              children: imgs.map((_, index) =>
                jsxRuntimeExports.jsx(
                  "div",
                  { className: `${css$1.point} ${state.selectIdx % (imgs.length || 0) == index && css$1.active}` },
                  index
                )
              )
            })
          })
        ]
      })
    ]
  });
};

export { Carousel as C };
