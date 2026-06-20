import { q as useUserInfoStore, y as useNavigate, V as useTranslation, cg as useParams, h as getWebType, r as reactExports, aB as useFlutterApp, i as getQueryVariable, cU as getBetDetail, aC as sendMessage, j as jsxRuntimeExports, a as joinClass, as as customToFixed, M as Modal, Q as getMoneyUnit, cV as getPromoBetRewardl, cJ as getPromoBetApply } from "./comps-B8ShbmG--2024_12_14_18_4.js";
import { c as css } from "./active.module-Ha5DJEPr-2024_12_14_18_4.js";
import { A as ActivityBottom } from "./index-oTqPuerr-2024_12_14_18_4.js";
import { f as CloseIconInMineIcon } from "./icons-Cdaou_E3-2024_12_14_18_4.js";
import { I as InnerPageWithBackContext } from "./index-DgUEEG5U-2024_12_14_18_4.js";
import "./index-CXpt7Cav-2024_12_14_18_4.js";
import "./index-DyG_R4zg-2024_12_14_18_4.js";
import "./index-4Dep-BeX-2024_12_14_18_4.js";
import "./index-CGmeRIXa-2024_12_14_18_4.js";
import "./index-BcpFmBv5-2024_12_14_18_4.js";
import "./index-CsbwU_08-2024_12_14_18_4.js";
import "./index-Bq-a07OY-2024_12_14_18_4.js";
import "./config-B4NCyDU0-2024_12_14_18_4.js";
const ActiveBet = () => {
  const { token, theme } = useUserInfoStore();
  useNavigate();
  const { t, i18n } = useTranslation();
  const { id, flag } = useParams();
  getWebType();
  const { setTitle } = reactExports.useContext(InnerPageWithBackContext);
  const [ruleList, setRuleList] = reactExports.useState([]);
  const [headImg, setHeadImg] = reactExports.useState({});
  const [dataList, setDataList] = reactExports.useState([]);
  const [detailStatus, setDetailStatus] = reactExports.useState(false);
  const { isApp } = useFlutterApp();
  const [amount, setAmount] = reactExports.useState(0);
  reactExports.useState(false);
  const [rid, setRid] = reactExports.useState("");
  const btnAmount = async () => {
    const [res] = await getPromoBetRewardl({ id, flag });
    if (res) {
      setAmount(res.total_amount || 0);
      if (res.bet_reward && res.bet_reward.length > 0) {
        let betId = res.bet_reward.map((item) => item.id).join();
        setRid(betId || "");
      } else {
        setRid("");
      }
    }
  };
  reactExports.useEffect(() => {
    token && btnAmount();
  }, [token]);
  reactExports.useEffect(() => {
    if (getQueryVariable("t")) {
      btnAmount();
    }
    getBetDetail({ id, flag }).then(([res]) => {
      var _a, _b, _c, _d;
      if (res) {
        const _title = (_b = (_a = res == null ? void 0 : res.config) == null ? void 0 : _a.title) != null ? _b : "-";
        if (isApp) {
          sendMessage("getTitleName", _title);
        }
        setTitle(_title);
        setDataList(((_c = res.rules) == null ? void 0 : _c.bonus_list) || []);
        setRuleList(((_d = res.rules) == null ? void 0 : _d.rule_text) || []);
        setHeadImg(res.static || {});
      }
    });
  }, []);
  const btnSubmit = async () => {
    if (token || getQueryVariable("t")) {
      const [res] = await getPromoBetApply({ id, rid });
      if (res) {
        setDetailStatus(true);
      }
    } else {
      useUserInfoStore.setState({ openForLogin: true });
    }
  };
  return /* @__PURE__ */ jsxRuntimeExports.jsxs(jsxRuntimeExports.Fragment, { children: [
    /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.box, children: [
      /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.amount, children: [
        /* @__PURE__ */ jsxRuntimeExports.jsxs("span", { className: css.placeholder, children: [
          t("目前可获得的奖励"),
          ":"
        ] }),
        /* @__PURE__ */ jsxRuntimeExports.jsx("span", { className: css.num, children: amount })
      ] }),
      (getQueryVariable("t") || token) && /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: css.btn, children: /* @__PURE__ */ jsxRuntimeExports.jsx(
        "div",
        {
          className: joinClass(
            css.receber,
            amount <= 0 ? css.disabled : "button"
          ),
          onClick: () => {
            if (amount > 0) {
              console.log(amount, "==========");
              btnSubmit();
            }
          },
          children: amount ? t("领取") : t("已领取")
        }
      ) }),
      /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: css.tableBox, children: /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.table, children: [
        /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.tableHead, children: [
          /* @__PURE__ */ jsxRuntimeExports.jsx("div", { children: t("Effective bet amount") }),
          /* @__PURE__ */ jsxRuntimeExports.jsx("div", { children: t("奖励金额2") })
        ] }),
        dataList.map((item, index) => {
          return /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.tr, children: [
            /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { children: [
              " ≥ ",
              customToFixed(item.bet_amount)
            ] }),
            /* @__PURE__ */ jsxRuntimeExports.jsx("div", { children: customToFixed(item.bonus_amount) })
          ] }, index);
        })
      ] }) }),
      /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: css.tableBox, children: /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.introduce, children: [
        /* @__PURE__ */ jsxRuntimeExports.jsxs("span", { children: [
          " ",
          t("活动规则"),
          ": "
        ] }),
        ruleList && ruleList.map((item, index) => {
          return /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { children: [
            index + 1,
            ".",
            item
          ] }, index);
        })
      ] }) })
    ] }),
    /* @__PURE__ */ jsxRuntimeExports.jsx(ActivityBottom, {}),
    /* @__PURE__ */ jsxRuntimeExports.jsx(Modal, { isOpen: detailStatus, onClose: (e) => e && e.stopPropagation(), children: /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.dialog, children: [
      /* @__PURE__ */ jsxRuntimeExports.jsx(
        "div",
        {
          className: css.close,
          onClick: () => {
            setDetailStatus(false);
          },
          children: /* @__PURE__ */ jsxRuntimeExports.jsx(CloseIconInMineIcon, {})
        }
      ),
      /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.successDialog, children: [
        /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: css.dialogTitle, children: t("提示") }),
        /* @__PURE__ */ jsxRuntimeExports.jsxs("div", { className: css.dialogDetails, children: [
          t("恭喜您已成功领取奖金奖励"),
          " ",
          /* @__PURE__ */ jsxRuntimeExports.jsxs("span", { children: [
            customToFixed(amount),
            getMoneyUnit()
          ] })
        ] }),
        /* @__PURE__ */ jsxRuntimeExports.jsx("div", { className: css.dialogBtn, children: /* @__PURE__ */ jsxRuntimeExports.jsx(
          "div",
          {
            className: css.btns,
            onClick: () => {
              setDetailStatus(false);
              setAmount(0);
            },
            children: t("确认")
          }
        ) })
      ] })
    ] }) })
  ] });
};
export {
  ActiveBet as default
};
