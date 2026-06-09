/**
* @vue/shared v3.5.13
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
/*! #__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function Ks(e) {
  const t = /* @__PURE__ */ Object.create(null);
  for (const s of e.split(",")) t[s] = 1;
  return (s) => s in t;
}
const H = {}, ot = [], Pe = () => {
}, Yr = () => !1, ss = (e) => e.charCodeAt(0) === 111 && e.charCodeAt(1) === 110 && // uppercase letter
(e.charCodeAt(2) > 122 || e.charCodeAt(2) < 97), Bs = (e) => e.startsWith("onUpdate:"), ue = Object.assign, Ws = (e, t) => {
  const s = e.indexOf(t);
  s > -1 && e.splice(s, 1);
}, zr = Object.prototype.hasOwnProperty, U = (e, t) => zr.call(e, t), R = Array.isArray, at = (e) => ns(e) === "[object Map]", Ln = (e) => ns(e) === "[object Set]", I = (e) => typeof e == "function", z = (e) => typeof e == "string", Ke = (e) => typeof e == "symbol", W = (e) => e !== null && typeof e == "object", Kn = (e) => (W(e) || I(e)) && I(e.then) && I(e.catch), Bn = Object.prototype.toString, ns = (e) => Bn.call(e), Xr = (e) => ns(e).slice(8, -1), Wn = (e) => ns(e) === "[object Object]", $s = (e) => z(e) && e !== "NaN" && e[0] !== "-" && "" + parseInt(e, 10) === e, vt = /* @__PURE__ */ Ks(
  // the leading comma is intentional so empty string "" is also included
  ",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"
), rs = (e) => {
  const t = /* @__PURE__ */ Object.create(null);
  return (s) => t[s] || (t[s] = e(s));
}, Zr = /-(\w)/g, je = rs(
  (e) => e.replace(Zr, (t, s) => s ? s.toUpperCase() : "")
), Qr = /\B([A-Z])/g, tt = rs(
  (e) => e.replace(Qr, "-$1").toLowerCase()
), is = rs((e) => e.charAt(0).toUpperCase() + e.slice(1)), gs = rs(
  (e) => e ? `on${is(e)}` : ""
), Le = (e, t) => !Object.is(e, t), Lt = (e, ...t) => {
  for (let s = 0; s < e.length; s++)
    e[s](...t);
}, $n = (e, t, s, n = !1) => {
  Object.defineProperty(e, t, {
    configurable: !0,
    enumerable: !1,
    writable: n,
    value: s
  });
}, As = (e) => {
  const t = parseFloat(e);
  return isNaN(t) ? e : t;
};
let hn;
const ls = () => hn || (hn = typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : typeof window < "u" ? window : typeof global < "u" ? global : {});
function qs(e) {
  if (R(e)) {
    const t = {};
    for (let s = 0; s < e.length; s++) {
      const n = e[s], r = z(n) ? ni(n) : qs(n);
      if (r)
        for (const i in r)
          t[i] = r[i];
    }
    return t;
  } else if (z(e) || W(e))
    return e;
}
const ei = /;(?![^(]*\))/g, ti = /:([^]+)/, si = /\/\*[^]*?\*\//g;
function ni(e) {
  const t = {};
  return e.replace(si, "").split(ei).forEach((s) => {
    if (s) {
      const n = s.split(ti);
      n.length > 1 && (t[n[0].trim()] = n[1].trim());
    }
  }), t;
}
function Ee(e) {
  let t = "";
  if (z(e))
    t = e;
  else if (R(e))
    for (let s = 0; s < e.length; s++) {
      const n = Ee(e[s]);
      n && (t += n + " ");
    }
  else if (W(e))
    for (const s in e)
      e[s] && (t += s + " ");
  return t.trim();
}
const ri = "itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly", ii = /* @__PURE__ */ Ks(ri);
function qn(e) {
  return !!e || e === "";
}
const Gn = (e) => !!(e && e.__v_isRef === !0), oe = (e) => z(e) ? e : e == null ? "" : R(e) || W(e) && (e.toString === Bn || !I(e.toString)) ? Gn(e) ? oe(e.value) : JSON.stringify(e, Yn, 2) : String(e), Yn = (e, t) => Gn(t) ? Yn(e, t.value) : at(t) ? {
  [`Map(${t.size})`]: [...t.entries()].reduce(
    (s, [n, r], i) => (s[ms(n, i) + " =>"] = r, s),
    {}
  )
} : Ln(t) ? {
  [`Set(${t.size})`]: [...t.values()].map((s) => ms(s))
} : Ke(t) ? ms(t) : W(t) && !R(t) && !Wn(t) ? String(t) : t, ms = (e, t = "") => {
  var s;
  return (
    // Symbol.description in es2019+ so we need to cast here to pass
    // the lib: es2016 check
    Ke(e) ? `Symbol(${(s = e.description) != null ? s : t})` : e
  );
};
/**
* @vue/reactivity v3.5.13
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let ge;
class li {
  constructor(t = !1) {
    this.detached = t, this._active = !0, this.effects = [], this.cleanups = [], this._isPaused = !1, this.parent = ge, !t && ge && (this.index = (ge.scopes || (ge.scopes = [])).push(
      this
    ) - 1);
  }
  get active() {
    return this._active;
  }
  pause() {
    if (this._active) {
      this._isPaused = !0;
      let t, s;
      if (this.scopes)
        for (t = 0, s = this.scopes.length; t < s; t++)
          this.scopes[t].pause();
      for (t = 0, s = this.effects.length; t < s; t++)
        this.effects[t].pause();
    }
  }
  /**
   * Resumes the effect scope, including all child scopes and effects.
   */
  resume() {
    if (this._active && this._isPaused) {
      this._isPaused = !1;
      let t, s;
      if (this.scopes)
        for (t = 0, s = this.scopes.length; t < s; t++)
          this.scopes[t].resume();
      for (t = 0, s = this.effects.length; t < s; t++)
        this.effects[t].resume();
    }
  }
  run(t) {
    if (this._active) {
      const s = ge;
      try {
        return ge = this, t();
      } finally {
        ge = s;
      }
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  on() {
    ge = this;
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  off() {
    ge = this.parent;
  }
  stop(t) {
    if (this._active) {
      this._active = !1;
      let s, n;
      for (s = 0, n = this.effects.length; s < n; s++)
        this.effects[s].stop();
      for (this.effects.length = 0, s = 0, n = this.cleanups.length; s < n; s++)
        this.cleanups[s]();
      if (this.cleanups.length = 0, this.scopes) {
        for (s = 0, n = this.scopes.length; s < n; s++)
          this.scopes[s].stop(!0);
        this.scopes.length = 0;
      }
      if (!this.detached && this.parent && !t) {
        const r = this.parent.scopes.pop();
        r && r !== this && (this.parent.scopes[this.index] = r, r.index = this.index);
      }
      this.parent = void 0;
    }
  }
}
function oi() {
  return ge;
}
let B;
const ys = /* @__PURE__ */ new WeakSet();
class zn {
  constructor(t) {
    this.fn = t, this.deps = void 0, this.depsTail = void 0, this.flags = 5, this.next = void 0, this.cleanup = void 0, this.scheduler = void 0, ge && ge.active && ge.effects.push(this);
  }
  pause() {
    this.flags |= 64;
  }
  resume() {
    this.flags & 64 && (this.flags &= -65, ys.has(this) && (ys.delete(this), this.trigger()));
  }
  /**
   * @internal
   */
  notify() {
    this.flags & 2 && !(this.flags & 32) || this.flags & 8 || Zn(this);
  }
  run() {
    if (!(this.flags & 1))
      return this.fn();
    this.flags |= 2, pn(this), Qn(this);
    const t = B, s = xe;
    B = this, xe = !0;
    try {
      return this.fn();
    } finally {
      er(this), B = t, xe = s, this.flags &= -3;
    }
  }
  stop() {
    if (this.flags & 1) {
      for (let t = this.deps; t; t = t.nextDep)
        zs(t);
      this.deps = this.depsTail = void 0, pn(this), this.onStop && this.onStop(), this.flags &= -2;
    }
  }
  trigger() {
    this.flags & 64 ? ys.add(this) : this.scheduler ? this.scheduler() : this.runIfDirty();
  }
  /**
   * @internal
   */
  runIfDirty() {
    Es(this) && this.run();
  }
  get dirty() {
    return Es(this);
  }
}
let Xn = 0, _t, wt;
function Zn(e, t = !1) {
  if (e.flags |= 8, t) {
    e.next = wt, wt = e;
    return;
  }
  e.next = _t, _t = e;
}
function Gs() {
  Xn++;
}
function Ys() {
  if (--Xn > 0)
    return;
  if (wt) {
    let t = wt;
    for (wt = void 0; t; ) {
      const s = t.next;
      t.next = void 0, t.flags &= -9, t = s;
    }
  }
  let e;
  for (; _t; ) {
    let t = _t;
    for (_t = void 0; t; ) {
      const s = t.next;
      if (t.next = void 0, t.flags &= -9, t.flags & 1)
        try {
          t.trigger();
        } catch (n) {
          e || (e = n);
        }
      t = s;
    }
  }
  if (e) throw e;
}
function Qn(e) {
  for (let t = e.deps; t; t = t.nextDep)
    t.version = -1, t.prevActiveLink = t.dep.activeLink, t.dep.activeLink = t;
}
function er(e) {
  let t, s = e.depsTail, n = s;
  for (; n; ) {
    const r = n.prevDep;
    n.version === -1 ? (n === s && (s = r), zs(n), ai(n)) : t = n, n.dep.activeLink = n.prevActiveLink, n.prevActiveLink = void 0, n = r;
  }
  e.deps = t, e.depsTail = s;
}
function Es(e) {
  for (let t = e.deps; t; t = t.nextDep)
    if (t.dep.version !== t.version || t.dep.computed && (tr(t.dep.computed) || t.dep.version !== t.version))
      return !0;
  return !!e._dirty;
}
function tr(e) {
  if (e.flags & 4 && !(e.flags & 16) || (e.flags &= -17, e.globalVersion === At))
    return;
  e.globalVersion = At;
  const t = e.dep;
  if (e.flags |= 2, t.version > 0 && !e.isSSR && e.deps && !Es(e)) {
    e.flags &= -3;
    return;
  }
  const s = B, n = xe;
  B = e, xe = !0;
  try {
    Qn(e);
    const r = e.fn(e._value);
    (t.version === 0 || Le(r, e._value)) && (e._value = r, t.version++);
  } catch (r) {
    throw t.version++, r;
  } finally {
    B = s, xe = n, er(e), e.flags &= -3;
  }
}
function zs(e, t = !1) {
  const { dep: s, prevSub: n, nextSub: r } = e;
  if (n && (n.nextSub = r, e.prevSub = void 0), r && (r.prevSub = n, e.nextSub = void 0), s.subs === e && (s.subs = n, !n && s.computed)) {
    s.computed.flags &= -5;
    for (let i = s.computed.deps; i; i = i.nextDep)
      zs(i, !0);
  }
  !t && !--s.sc && s.map && s.map.delete(s.key);
}
function ai(e) {
  const { prevDep: t, nextDep: s } = e;
  t && (t.nextDep = s, e.prevDep = void 0), s && (s.prevDep = t, e.nextDep = void 0);
}
let xe = !0;
const sr = [];
function Be() {
  sr.push(xe), xe = !1;
}
function We() {
  const e = sr.pop();
  xe = e === void 0 ? !0 : e;
}
function pn(e) {
  const { cleanup: t } = e;
  if (e.cleanup = void 0, t) {
    const s = B;
    B = void 0;
    try {
      t();
    } finally {
      B = s;
    }
  }
}
let At = 0;
class ui {
  constructor(t, s) {
    this.sub = t, this.dep = s, this.version = s.version, this.nextDep = this.prevDep = this.nextSub = this.prevSub = this.prevActiveLink = void 0;
  }
}
class Xs {
  constructor(t) {
    this.computed = t, this.version = 0, this.activeLink = void 0, this.subs = void 0, this.map = void 0, this.key = void 0, this.sc = 0;
  }
  track(t) {
    if (!B || !xe || B === this.computed)
      return;
    let s = this.activeLink;
    if (s === void 0 || s.sub !== B)
      s = this.activeLink = new ui(B, this), B.deps ? (s.prevDep = B.depsTail, B.depsTail.nextDep = s, B.depsTail = s) : B.deps = B.depsTail = s, nr(s);
    else if (s.version === -1 && (s.version = this.version, s.nextDep)) {
      const n = s.nextDep;
      n.prevDep = s.prevDep, s.prevDep && (s.prevDep.nextDep = n), s.prevDep = B.depsTail, s.nextDep = void 0, B.depsTail.nextDep = s, B.depsTail = s, B.deps === s && (B.deps = n);
    }
    return s;
  }
  trigger(t) {
    this.version++, At++, this.notify(t);
  }
  notify(t) {
    Gs();
    try {
      for (let s = this.subs; s; s = s.prevSub)
        s.sub.notify() && s.sub.dep.notify();
    } finally {
      Ys();
    }
  }
}
function nr(e) {
  if (e.dep.sc++, e.sub.flags & 4) {
    const t = e.dep.computed;
    if (t && !e.dep.subs) {
      t.flags |= 20;
      for (let n = t.deps; n; n = n.nextDep)
        nr(n);
    }
    const s = e.dep.subs;
    s !== e && (e.prevSub = s, s && (s.nextSub = e)), e.dep.subs = e;
  }
}
const Vs = /* @__PURE__ */ new WeakMap(), Ze = Symbol(
  ""
), Os = Symbol(
  ""
), Et = Symbol(
  ""
);
function te(e, t, s) {
  if (xe && B) {
    let n = Vs.get(e);
    n || Vs.set(e, n = /* @__PURE__ */ new Map());
    let r = n.get(s);
    r || (n.set(s, r = new Xs()), r.map = n, r.key = s), r.track();
  }
}
function Ne(e, t, s, n, r, i) {
  const l = Vs.get(e);
  if (!l) {
    At++;
    return;
  }
  const o = (a) => {
    a && a.trigger();
  };
  if (Gs(), t === "clear")
    l.forEach(o);
  else {
    const a = R(e), d = a && $s(s);
    if (a && s === "length") {
      const c = Number(n);
      l.forEach((p, T) => {
        (T === "length" || T === Et || !Ke(T) && T >= c) && o(p);
      });
    } else
      switch ((s !== void 0 || l.has(void 0)) && o(l.get(s)), d && o(l.get(Et)), t) {
        case "add":
          a ? d && o(l.get("length")) : (o(l.get(Ze)), at(e) && o(l.get(Os)));
          break;
        case "delete":
          a || (o(l.get(Ze)), at(e) && o(l.get(Os)));
          break;
        case "set":
          at(e) && o(l.get(Ze));
          break;
      }
  }
  Ys();
}
function st(e) {
  const t = F(e);
  return t === e ? t : (te(t, "iterate", Et), be(e) ? t : t.map(se));
}
function os(e) {
  return te(e = F(e), "iterate", Et), e;
}
const fi = {
  __proto__: null,
  [Symbol.iterator]() {
    return bs(this, Symbol.iterator, se);
  },
  concat(...e) {
    return st(this).concat(
      ...e.map((t) => R(t) ? st(t) : t)
    );
  },
  entries() {
    return bs(this, "entries", (e) => (e[1] = se(e[1]), e));
  },
  every(e, t) {
    return Ie(this, "every", e, t, void 0, arguments);
  },
  filter(e, t) {
    return Ie(this, "filter", e, t, (s) => s.map(se), arguments);
  },
  find(e, t) {
    return Ie(this, "find", e, t, se, arguments);
  },
  findIndex(e, t) {
    return Ie(this, "findIndex", e, t, void 0, arguments);
  },
  findLast(e, t) {
    return Ie(this, "findLast", e, t, se, arguments);
  },
  findLastIndex(e, t) {
    return Ie(this, "findLastIndex", e, t, void 0, arguments);
  },
  // flat, flatMap could benefit from ARRAY_ITERATE but are not straight-forward to implement
  forEach(e, t) {
    return Ie(this, "forEach", e, t, void 0, arguments);
  },
  includes(...e) {
    return js(this, "includes", e);
  },
  indexOf(...e) {
    return js(this, "indexOf", e);
  },
  join(e) {
    return st(this).join(e);
  },
  // keys() iterator only reads `length`, no optimisation required
  lastIndexOf(...e) {
    return js(this, "lastIndexOf", e);
  },
  map(e, t) {
    return Ie(this, "map", e, t, void 0, arguments);
  },
  pop() {
    return yt(this, "pop");
  },
  push(...e) {
    return yt(this, "push", e);
  },
  reduce(e, ...t) {
    return gn(this, "reduce", e, t);
  },
  reduceRight(e, ...t) {
    return gn(this, "reduceRight", e, t);
  },
  shift() {
    return yt(this, "shift");
  },
  // slice could use ARRAY_ITERATE but also seems to beg for range tracking
  some(e, t) {
    return Ie(this, "some", e, t, void 0, arguments);
  },
  splice(...e) {
    return yt(this, "splice", e);
  },
  toReversed() {
    return st(this).toReversed();
  },
  toSorted(e) {
    return st(this).toSorted(e);
  },
  toSpliced(...e) {
    return st(this).toSpliced(...e);
  },
  unshift(...e) {
    return yt(this, "unshift", e);
  },
  values() {
    return bs(this, "values", se);
  }
};
function bs(e, t, s) {
  const n = os(e), r = n[t]();
  return n !== e && !be(e) && (r._next = r.next, r.next = () => {
    const i = r._next();
    return i.value && (i.value = s(i.value)), i;
  }), r;
}
const ci = Array.prototype;
function Ie(e, t, s, n, r, i) {
  const l = os(e), o = l !== e && !be(e), a = l[t];
  if (a !== ci[t]) {
    const p = a.apply(e, i);
    return o ? se(p) : p;
  }
  let d = s;
  l !== e && (o ? d = function(p, T) {
    return s.call(this, se(p), T, e);
  } : s.length > 2 && (d = function(p, T) {
    return s.call(this, p, T, e);
  }));
  const c = a.call(l, d, n);
  return o && r ? r(c) : c;
}
function gn(e, t, s, n) {
  const r = os(e);
  let i = s;
  return r !== e && (be(e) ? s.length > 3 && (i = function(l, o, a) {
    return s.call(this, l, o, a, e);
  }) : i = function(l, o, a) {
    return s.call(this, l, se(o), a, e);
  }), r[t](i, ...n);
}
function js(e, t, s) {
  const n = F(e);
  te(n, "iterate", Et);
  const r = n[t](...s);
  return (r === -1 || r === !1) && tn(s[0]) ? (s[0] = F(s[0]), n[t](...s)) : r;
}
function yt(e, t, s = []) {
  Be(), Gs();
  const n = F(e)[t].apply(e, s);
  return Ys(), We(), n;
}
const di = /* @__PURE__ */ Ks("__proto__,__v_isRef,__isVue"), rr = new Set(
  /* @__PURE__ */ Object.getOwnPropertyNames(Symbol).filter((e) => e !== "arguments" && e !== "caller").map((e) => Symbol[e]).filter(Ke)
);
function hi(e) {
  Ke(e) || (e = String(e));
  const t = F(this);
  return te(t, "has", e), t.hasOwnProperty(e);
}
class ir {
  constructor(t = !1, s = !1) {
    this._isReadonly = t, this._isShallow = s;
  }
  get(t, s, n) {
    if (s === "__v_skip") return t.__v_skip;
    const r = this._isReadonly, i = this._isShallow;
    if (s === "__v_isReactive")
      return !r;
    if (s === "__v_isReadonly")
      return r;
    if (s === "__v_isShallow")
      return i;
    if (s === "__v_raw")
      return n === (r ? i ? wi : ur : i ? ar : or).get(t) || // receiver is not the reactive proxy, but has the same prototype
      // this means the receiver is a user proxy of the reactive proxy
      Object.getPrototypeOf(t) === Object.getPrototypeOf(n) ? t : void 0;
    const l = R(t);
    if (!r) {
      let a;
      if (l && (a = fi[s]))
        return a;
      if (s === "hasOwnProperty")
        return hi;
    }
    const o = Reflect.get(
      t,
      s,
      // if this is a proxy wrapping a ref, return methods using the raw ref
      // as receiver so that we don't have to call `toRaw` on the ref in all
      // its class methods
      re(t) ? t : n
    );
    return (Ke(s) ? rr.has(s) : di(s)) || (r || te(t, "get", s), i) ? o : re(o) ? l && $s(s) ? o : o.value : W(o) ? r ? fr(o) : Qs(o) : o;
  }
}
class lr extends ir {
  constructor(t = !1) {
    super(!1, t);
  }
  set(t, s, n, r) {
    let i = t[s];
    if (!this._isShallow) {
      const a = Qe(i);
      if (!be(n) && !Qe(n) && (i = F(i), n = F(n)), !R(t) && re(i) && !re(n))
        return a ? !1 : (i.value = n, !0);
    }
    const l = R(t) && $s(s) ? Number(s) < t.length : U(t, s), o = Reflect.set(
      t,
      s,
      n,
      re(t) ? t : r
    );
    return t === F(r) && (l ? Le(n, i) && Ne(t, "set", s, n) : Ne(t, "add", s, n)), o;
  }
  deleteProperty(t, s) {
    const n = U(t, s);
    t[s];
    const r = Reflect.deleteProperty(t, s);
    return r && n && Ne(t, "delete", s, void 0), r;
  }
  has(t, s) {
    const n = Reflect.has(t, s);
    return (!Ke(s) || !rr.has(s)) && te(t, "has", s), n;
  }
  ownKeys(t) {
    return te(
      t,
      "iterate",
      R(t) ? "length" : Ze
    ), Reflect.ownKeys(t);
  }
}
class pi extends ir {
  constructor(t = !1) {
    super(!0, t);
  }
  set(t, s) {
    return !0;
  }
  deleteProperty(t, s) {
    return !0;
  }
}
const gi = /* @__PURE__ */ new lr(), mi = /* @__PURE__ */ new pi(), yi = /* @__PURE__ */ new lr(!0);
const Ps = (e) => e, kt = (e) => Reflect.getPrototypeOf(e);
function bi(e, t, s) {
  return function(...n) {
    const r = this.__v_raw, i = F(r), l = at(i), o = e === "entries" || e === Symbol.iterator && l, a = e === "keys" && l, d = r[e](...n), c = s ? Ps : t ? Rs : se;
    return !t && te(
      i,
      "iterate",
      a ? Os : Ze
    ), {
      // iterator protocol
      next() {
        const { value: p, done: T } = d.next();
        return T ? { value: p, done: T } : {
          value: o ? [c(p[0]), c(p[1])] : c(p),
          done: T
        };
      },
      // iterable protocol
      [Symbol.iterator]() {
        return this;
      }
    };
  };
}
function Jt(e) {
  return function(...t) {
    return e === "delete" ? !1 : e === "clear" ? void 0 : this;
  };
}
function ji(e, t) {
  const s = {
    get(r) {
      const i = this.__v_raw, l = F(i), o = F(r);
      e || (Le(r, o) && te(l, "get", r), te(l, "get", o));
      const { has: a } = kt(l), d = t ? Ps : e ? Rs : se;
      if (a.call(l, r))
        return d(i.get(r));
      if (a.call(l, o))
        return d(i.get(o));
      i !== l && i.get(r);
    },
    get size() {
      const r = this.__v_raw;
      return !e && te(F(r), "iterate", Ze), Reflect.get(r, "size", r);
    },
    has(r) {
      const i = this.__v_raw, l = F(i), o = F(r);
      return e || (Le(r, o) && te(l, "has", r), te(l, "has", o)), r === o ? i.has(r) : i.has(r) || i.has(o);
    },
    forEach(r, i) {
      const l = this, o = l.__v_raw, a = F(o), d = t ? Ps : e ? Rs : se;
      return !e && te(a, "iterate", Ze), o.forEach((c, p) => r.call(i, d(c), d(p), l));
    }
  };
  return ue(
    s,
    e ? {
      add: Jt("add"),
      set: Jt("set"),
      delete: Jt("delete"),
      clear: Jt("clear")
    } : {
      add(r) {
        !t && !be(r) && !Qe(r) && (r = F(r));
        const i = F(this);
        return kt(i).has.call(i, r) || (i.add(r), Ne(i, "add", r, r)), this;
      },
      set(r, i) {
        !t && !be(i) && !Qe(i) && (i = F(i));
        const l = F(this), { has: o, get: a } = kt(l);
        let d = o.call(l, r);
        d || (r = F(r), d = o.call(l, r));
        const c = a.call(l, r);
        return l.set(r, i), d ? Le(i, c) && Ne(l, "set", r, i) : Ne(l, "add", r, i), this;
      },
      delete(r) {
        const i = F(this), { has: l, get: o } = kt(i);
        let a = l.call(i, r);
        a || (r = F(r), a = l.call(i, r)), o && o.call(i, r);
        const d = i.delete(r);
        return a && Ne(i, "delete", r, void 0), d;
      },
      clear() {
        const r = F(this), i = r.size !== 0, l = r.clear();
        return i && Ne(
          r,
          "clear",
          void 0,
          void 0
        ), l;
      }
    }
  ), [
    "keys",
    "values",
    "entries",
    Symbol.iterator
  ].forEach((r) => {
    s[r] = bi(r, e, t);
  }), s;
}
function Zs(e, t) {
  const s = ji(e, t);
  return (n, r, i) => r === "__v_isReactive" ? !e : r === "__v_isReadonly" ? e : r === "__v_raw" ? n : Reflect.get(
    U(s, r) && r in n ? s : n,
    r,
    i
  );
}
const xi = {
  get: /* @__PURE__ */ Zs(!1, !1)
}, vi = {
  get: /* @__PURE__ */ Zs(!1, !0)
}, _i = {
  get: /* @__PURE__ */ Zs(!0, !1)
};
const or = /* @__PURE__ */ new WeakMap(), ar = /* @__PURE__ */ new WeakMap(), ur = /* @__PURE__ */ new WeakMap(), wi = /* @__PURE__ */ new WeakMap();
function Ti(e) {
  switch (e) {
    case "Object":
    case "Array":
      return 1;
    case "Map":
    case "Set":
    case "WeakMap":
    case "WeakSet":
      return 2;
    default:
      return 0;
  }
}
function Ci(e) {
  return e.__v_skip || !Object.isExtensible(e) ? 0 : Ti(Xr(e));
}
function Qs(e) {
  return Qe(e) ? e : en(
    e,
    !1,
    gi,
    xi,
    or
  );
}
function Si(e) {
  return en(
    e,
    !1,
    yi,
    vi,
    ar
  );
}
function fr(e) {
  return en(
    e,
    !0,
    mi,
    _i,
    ur
  );
}
function en(e, t, s, n, r) {
  if (!W(e) || e.__v_raw && !(t && e.__v_isReactive))
    return e;
  const i = r.get(e);
  if (i)
    return i;
  const l = Ci(e);
  if (l === 0)
    return e;
  const o = new Proxy(
    e,
    l === 2 ? n : s
  );
  return r.set(e, o), o;
}
function ut(e) {
  return Qe(e) ? ut(e.__v_raw) : !!(e && e.__v_isReactive);
}
function Qe(e) {
  return !!(e && e.__v_isReadonly);
}
function be(e) {
  return !!(e && e.__v_isShallow);
}
function tn(e) {
  return e ? !!e.__v_raw : !1;
}
function F(e) {
  const t = e && e.__v_raw;
  return t ? F(t) : e;
}
function Ai(e) {
  return !U(e, "__v_skip") && Object.isExtensible(e) && $n(e, "__v_skip", !0), e;
}
const se = (e) => W(e) ? Qs(e) : e, Rs = (e) => W(e) ? fr(e) : e;
function re(e) {
  return e ? e.__v_isRef === !0 : !1;
}
function Ei(e) {
  return cr(e, !1);
}
function Vi(e) {
  return cr(e, !0);
}
function cr(e, t) {
  return re(e) ? e : new Oi(e, t);
}
class Oi {
  constructor(t, s) {
    this.dep = new Xs(), this.__v_isRef = !0, this.__v_isShallow = !1, this._rawValue = s ? t : F(t), this._value = s ? t : se(t), this.__v_isShallow = s;
  }
  get value() {
    return this.dep.track(), this._value;
  }
  set value(t) {
    const s = this._rawValue, n = this.__v_isShallow || be(t) || Qe(t);
    t = n ? t : F(t), Le(t, s) && (this._rawValue = t, this._value = n ? t : se(t), this.dep.trigger());
  }
}
function Pi(e) {
  return re(e) ? e.value : e;
}
const Ri = {
  get: (e, t, s) => t === "__v_raw" ? e : Pi(Reflect.get(e, t, s)),
  set: (e, t, s, n) => {
    const r = e[t];
    return re(r) && !re(s) ? (r.value = s, !0) : Reflect.set(e, t, s, n);
  }
};
function dr(e) {
  return ut(e) ? e : new Proxy(e, Ri);
}
class Ii {
  constructor(t, s, n) {
    this.fn = t, this.setter = s, this._value = void 0, this.dep = new Xs(this), this.__v_isRef = !0, this.deps = void 0, this.depsTail = void 0, this.flags = 16, this.globalVersion = At - 1, this.next = void 0, this.effect = this, this.__v_isReadonly = !s, this.isSSR = n;
  }
  /**
   * @internal
   */
  notify() {
    if (this.flags |= 16, !(this.flags & 8) && // avoid infinite self recursion
    B !== this)
      return Zn(this, !0), !0;
  }
  get value() {
    const t = this.dep.track();
    return tr(this), t && (t.version = this.dep.version), this._value;
  }
  set value(t) {
    this.setter && this.setter(t);
  }
}
function Mi(e, t, s = !1) {
  let n, r;
  return I(e) ? n = e : (n = e.get, r = e.set), new Ii(n, r, s);
}
const Ht = {}, qt = /* @__PURE__ */ new WeakMap();
let Xe;
function Ni(e, t = !1, s = Xe) {
  if (s) {
    let n = qt.get(s);
    n || qt.set(s, n = []), n.push(e);
  }
}
function Di(e, t, s = H) {
  const { immediate: n, deep: r, once: i, scheduler: l, augmentJob: o, call: a } = s, d = (O) => r ? O : be(O) || r === !1 || r === 0 ? De(O, 1) : De(O);
  let c, p, T, C, M = !1, D = !1;
  if (re(e) ? (p = () => e.value, M = be(e)) : ut(e) ? (p = () => d(e), M = !0) : R(e) ? (D = !0, M = e.some((O) => ut(O) || be(O)), p = () => e.map((O) => {
    if (re(O))
      return O.value;
    if (ut(O))
      return d(O);
    if (I(O))
      return a ? a(O, 2) : O();
  })) : I(e) ? t ? p = a ? () => a(e, 2) : e : p = () => {
    if (T) {
      Be();
      try {
        T();
      } finally {
        We();
      }
    }
    const O = Xe;
    Xe = c;
    try {
      return a ? a(e, 3, [C]) : e(C);
    } finally {
      Xe = O;
    }
  } : p = Pe, t && r) {
    const O = p, X = r === !0 ? 1 / 0 : r;
    p = () => De(O(), X);
  }
  const ee = oi(), J = () => {
    c.stop(), ee && ee.active && Ws(ee.effects, c);
  };
  if (i && t) {
    const O = t;
    t = (...X) => {
      O(...X), J();
    };
  }
  let G = D ? new Array(e.length).fill(Ht) : Ht;
  const Y = (O) => {
    if (!(!(c.flags & 1) || !c.dirty && !O))
      if (t) {
        const X = c.run();
        if (r || M || (D ? X.some((Ue, ve) => Le(Ue, G[ve])) : Le(X, G))) {
          T && T();
          const Ue = Xe;
          Xe = c;
          try {
            const ve = [
              X,
              // pass undefined as the old value when it's changed for the first time
              G === Ht ? void 0 : D && G[0] === Ht ? [] : G,
              C
            ];
            a ? a(t, 3, ve) : (
              // @ts-expect-error
              t(...ve)
            ), G = X;
          } finally {
            Xe = Ue;
          }
        }
      } else
        c.run();
  };
  return o && o(Y), c = new zn(p), c.scheduler = l ? () => l(Y, !1) : Y, C = (O) => Ni(O, !1, c), T = c.onStop = () => {
    const O = qt.get(c);
    if (O) {
      if (a)
        a(O, 4);
      else
        for (const X of O) X();
      qt.delete(c);
    }
  }, t ? n ? Y(!0) : G = c.run() : l ? l(Y.bind(null, !0), !0) : c.run(), J.pause = c.pause.bind(c), J.resume = c.resume.bind(c), J.stop = J, J;
}
function De(e, t = 1 / 0, s) {
  if (t <= 0 || !W(e) || e.__v_skip || (s = s || /* @__PURE__ */ new Set(), s.has(e)))
    return e;
  if (s.add(e), t--, re(e))
    De(e.value, t, s);
  else if (R(e))
    for (let n = 0; n < e.length; n++)
      De(e[n], t, s);
  else if (Ln(e) || at(e))
    e.forEach((n) => {
      De(n, t, s);
    });
  else if (Wn(e)) {
    for (const n in e)
      De(e[n], t, s);
    for (const n of Object.getOwnPropertySymbols(e))
      Object.prototype.propertyIsEnumerable.call(e, n) && De(e[n], t, s);
  }
  return e;
}
/**
* @vue/runtime-core v3.5.13
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
function Rt(e, t, s, n) {
  try {
    return n ? e(...n) : e();
  } catch (r) {
    as(r, t, s);
  }
}
function Re(e, t, s, n) {
  if (I(e)) {
    const r = Rt(e, t, s, n);
    return r && Kn(r) && r.catch((i) => {
      as(i, t, s);
    }), r;
  }
  if (R(e)) {
    const r = [];
    for (let i = 0; i < e.length; i++)
      r.push(Re(e[i], t, s, n));
    return r;
  }
}
function as(e, t, s, n = !0) {
  const r = t ? t.vnode : null, { errorHandler: i, throwUnhandledErrorInProduction: l } = t && t.appContext.config || H;
  if (t) {
    let o = t.parent;
    const a = t.proxy, d = `https://vuejs.org/error-reference/#runtime-${s}`;
    for (; o; ) {
      const c = o.ec;
      if (c) {
        for (let p = 0; p < c.length; p++)
          if (c[p](e, a, d) === !1)
            return;
      }
      o = o.parent;
    }
    if (i) {
      Be(), Rt(i, null, 10, [
        e,
        a,
        d
      ]), We();
      return;
    }
  }
  Fi(e, s, r, n, l);
}
function Fi(e, t, s, n = !0, r = !1) {
  if (r)
    throw e;
  console.error(e);
}
const ae = [];
let Ae = -1;
const ft = [];
let Je = null, nt = 0;
const hr = /* @__PURE__ */ Promise.resolve();
let Gt = null;
function Ui(e) {
  const t = Gt || hr;
  return e ? t.then(this ? e.bind(this) : e) : t;
}
function ki(e) {
  let t = Ae + 1, s = ae.length;
  for (; t < s; ) {
    const n = t + s >>> 1, r = ae[n], i = Vt(r);
    i < e || i === e && r.flags & 2 ? t = n + 1 : s = n;
  }
  return t;
}
function sn(e) {
  if (!(e.flags & 1)) {
    const t = Vt(e), s = ae[ae.length - 1];
    !s || // fast path when the job id is larger than the tail
    !(e.flags & 2) && t >= Vt(s) ? ae.push(e) : ae.splice(ki(t), 0, e), e.flags |= 1, pr();
  }
}
function pr() {
  Gt || (Gt = hr.then(mr));
}
function Ji(e) {
  R(e) ? ft.push(...e) : Je && e.id === -1 ? Je.splice(nt + 1, 0, e) : e.flags & 1 || (ft.push(e), e.flags |= 1), pr();
}
function mn(e, t, s = Ae + 1) {
  for (; s < ae.length; s++) {
    const n = ae[s];
    if (n && n.flags & 2) {
      if (e && n.id !== e.uid)
        continue;
      ae.splice(s, 1), s--, n.flags & 4 && (n.flags &= -2), n(), n.flags & 4 || (n.flags &= -2);
    }
  }
}
function gr(e) {
  if (ft.length) {
    const t = [...new Set(ft)].sort(
      (s, n) => Vt(s) - Vt(n)
    );
    if (ft.length = 0, Je) {
      Je.push(...t);
      return;
    }
    for (Je = t, nt = 0; nt < Je.length; nt++) {
      const s = Je[nt];
      s.flags & 4 && (s.flags &= -2), s.flags & 8 || s(), s.flags &= -2;
    }
    Je = null, nt = 0;
  }
}
const Vt = (e) => e.id == null ? e.flags & 2 ? -1 : 1 / 0 : e.id;
function mr(e) {
  try {
    for (Ae = 0; Ae < ae.length; Ae++) {
      const t = ae[Ae];
      t && !(t.flags & 8) && (t.flags & 4 && (t.flags &= -2), Rt(
        t,
        t.i,
        t.i ? 15 : 14
      ), t.flags & 4 || (t.flags &= -2));
    }
  } finally {
    for (; Ae < ae.length; Ae++) {
      const t = ae[Ae];
      t && (t.flags &= -2);
    }
    Ae = -1, ae.length = 0, gr(), Gt = null, (ae.length || ft.length) && mr();
  }
}
let me = null, yr = null;
function Yt(e) {
  const t = me;
  return me = e, yr = e && e.type.__scopeId || null, t;
}
function Hi(e, t = me, s) {
  if (!t || e._n)
    return e;
  const n = (...r) => {
    n._d && Sn(-1);
    const i = Yt(t);
    let l;
    try {
      l = e(...r);
    } finally {
      Yt(i), n._d && Sn(1);
    }
    return l;
  };
  return n._n = !0, n._c = !0, n._d = !0, n;
}
function Oe(e, t) {
  if (me === null)
    return e;
  const s = ds(me), n = e.dirs || (e.dirs = []);
  for (let r = 0; r < t.length; r++) {
    let [i, l, o, a = H] = t[r];
    i && (I(i) && (i = {
      mounted: i,
      updated: i
    }), i.deep && De(l), n.push({
      dir: i,
      instance: s,
      value: l,
      oldValue: void 0,
      arg: o,
      modifiers: a
    }));
  }
  return e;
}
function Ye(e, t, s, n) {
  const r = e.dirs, i = t && t.dirs;
  for (let l = 0; l < r.length; l++) {
    const o = r[l];
    i && (o.oldValue = i[l].value);
    let a = o.dir[n];
    a && (Be(), Re(a, s, 8, [
      e.el,
      o,
      e,
      t
    ]), We());
  }
}
const Li = Symbol("_vte"), Ki = (e) => e.__isTeleport;
function nn(e, t) {
  e.shapeFlag & 6 && e.component ? (e.transition = t, nn(e.component.subTree, t)) : e.shapeFlag & 128 ? (e.ssContent.transition = t.clone(e.ssContent), e.ssFallback.transition = t.clone(e.ssFallback)) : e.transition = t;
}
function br(e) {
  e.ids = [e.ids[0] + e.ids[2]++ + "-", 0, 0];
}
function zt(e, t, s, n, r = !1) {
  if (R(e)) {
    e.forEach(
      (M, D) => zt(
        M,
        t && (R(t) ? t[D] : t),
        s,
        n,
        r
      )
    );
    return;
  }
  if (Tt(n) && !r) {
    n.shapeFlag & 512 && n.type.__asyncResolved && n.component.subTree.component && zt(e, t, s, n.component.subTree);
    return;
  }
  const i = n.shapeFlag & 4 ? ds(n.component) : n.el, l = r ? null : i, { i: o, r: a } = e, d = t && t.r, c = o.refs === H ? o.refs = {} : o.refs, p = o.setupState, T = F(p), C = p === H ? () => !1 : (M) => U(T, M);
  if (d != null && d !== a && (z(d) ? (c[d] = null, C(d) && (p[d] = null)) : re(d) && (d.value = null)), I(a))
    Rt(a, o, 12, [l, c]);
  else {
    const M = z(a), D = re(a);
    if (M || D) {
      const ee = () => {
        if (e.f) {
          const J = M ? C(a) ? p[a] : c[a] : a.value;
          r ? R(J) && Ws(J, i) : R(J) ? J.includes(i) || J.push(i) : M ? (c[a] = [i], C(a) && (p[a] = c[a])) : (a.value = [i], e.k && (c[e.k] = a.value));
        } else M ? (c[a] = l, C(a) && (p[a] = l)) : D && (a.value = l, e.k && (c[e.k] = l));
      };
      l ? (ee.id = -1, pe(ee, s)) : ee();
    }
  }
}
ls().requestIdleCallback;
ls().cancelIdleCallback;
const Tt = (e) => !!e.type.__asyncLoader, jr = (e) => e.type.__isKeepAlive;
function Bi(e, t) {
  xr(e, "a", t);
}
function Wi(e, t) {
  xr(e, "da", t);
}
function xr(e, t, s = ne) {
  const n = e.__wdc || (e.__wdc = () => {
    let r = s;
    for (; r; ) {
      if (r.isDeactivated)
        return;
      r = r.parent;
    }
    return e();
  });
  if (us(t, n, s), s) {
    let r = s.parent;
    for (; r && r.parent; )
      jr(r.parent.vnode) && $i(n, t, s, r), r = r.parent;
  }
}
function $i(e, t, s, n) {
  const r = us(
    t,
    e,
    n,
    !0
    /* prepend */
  );
  vr(() => {
    Ws(n[t], r);
  }, s);
}
function us(e, t, s = ne, n = !1) {
  if (s) {
    const r = s[e] || (s[e] = []), i = t.__weh || (t.__weh = (...l) => {
      Be();
      const o = It(s), a = Re(t, s, e, l);
      return o(), We(), a;
    });
    return n ? r.unshift(i) : r.push(i), i;
  }
}
const Fe = (e) => (t, s = ne) => {
  (!Pt || e === "sp") && us(e, (...n) => t(...n), s);
}, qi = Fe("bm"), Gi = Fe("m"), Yi = Fe(
  "bu"
), zi = Fe("u"), Xi = Fe(
  "bum"
), vr = Fe("um"), Zi = Fe(
  "sp"
), Qi = Fe("rtg"), el = Fe("rtc");
function tl(e, t = ne) {
  us("ec", e, t);
}
const sl = "components";
function Is(e, t) {
  return rl(sl, e, !0, t) || e;
}
const nl = Symbol.for("v-ndc");
function rl(e, t, s = !0, n = !1) {
  const r = me || ne;
  if (r) {
    const i = r.type;
    {
      const o = $l(
        i,
        !1
      );
      if (o && (o === t || o === je(t) || o === is(je(t))))
        return i;
    }
    const l = (
      // local registration
      // check instance[type] first which is resolved for options API
      yn(r[e] || i[e], t) || // global registration
      yn(r.appContext[e], t)
    );
    return !l && n ? i : l;
  }
}
function yn(e, t) {
  return e && (e[t] || e[je(t)] || e[is(je(t))]);
}
function Ms(e, t, s, n) {
  let r;
  const i = s, l = R(e);
  if (l || z(e)) {
    const o = l && ut(e);
    let a = !1;
    o && (a = !be(e), e = os(e)), r = new Array(e.length);
    for (let d = 0, c = e.length; d < c; d++)
      r[d] = t(
        a ? se(e[d]) : e[d],
        d,
        void 0,
        i
      );
  } else if (typeof e == "number") {
    r = new Array(e);
    for (let o = 0; o < e; o++)
      r[o] = t(o + 1, o, void 0, i);
  } else if (W(e))
    if (e[Symbol.iterator])
      r = Array.from(
        e,
        (o, a) => t(o, a, void 0, i)
      );
    else {
      const o = Object.keys(e);
      r = new Array(o.length);
      for (let a = 0, d = o.length; a < d; a++) {
        const c = o[a];
        r[a] = t(e[c], c, a, i);
      }
    }
  else
    r = [];
  return r;
}
const Ns = (e) => e ? Lr(e) ? ds(e) : Ns(e.parent) : null, Ct = (
  // Move PURE marker to new line to workaround compiler discarding it
  // due to type annotation
  /* @__PURE__ */ ue(/* @__PURE__ */ Object.create(null), {
    $: (e) => e,
    $el: (e) => e.vnode.el,
    $data: (e) => e.data,
    $props: (e) => e.props,
    $attrs: (e) => e.attrs,
    $slots: (e) => e.slots,
    $refs: (e) => e.refs,
    $parent: (e) => Ns(e.parent),
    $root: (e) => Ns(e.root),
    $host: (e) => e.ce,
    $emit: (e) => e.emit,
    $options: (e) => wr(e),
    $forceUpdate: (e) => e.f || (e.f = () => {
      sn(e.update);
    }),
    $nextTick: (e) => e.n || (e.n = Ui.bind(e.proxy)),
    $watch: (e) => Sl.bind(e)
  })
), xs = (e, t) => e !== H && !e.__isScriptSetup && U(e, t), il = {
  get({ _: e }, t) {
    if (t === "__v_skip")
      return !0;
    const { ctx: s, setupState: n, data: r, props: i, accessCache: l, type: o, appContext: a } = e;
    let d;
    if (t[0] !== "$") {
      const C = l[t];
      if (C !== void 0)
        switch (C) {
          case 1:
            return n[t];
          case 2:
            return r[t];
          case 4:
            return s[t];
          case 3:
            return i[t];
        }
      else {
        if (xs(n, t))
          return l[t] = 1, n[t];
        if (r !== H && U(r, t))
          return l[t] = 2, r[t];
        if (
          // only cache other properties when instance has declared (thus stable)
          // props
          (d = e.propsOptions[0]) && U(d, t)
        )
          return l[t] = 3, i[t];
        if (s !== H && U(s, t))
          return l[t] = 4, s[t];
        Ds && (l[t] = 0);
      }
    }
    const c = Ct[t];
    let p, T;
    if (c)
      return t === "$attrs" && te(e.attrs, "get", ""), c(e);
    if (
      // css module (injected by vue-loader)
      (p = o.__cssModules) && (p = p[t])
    )
      return p;
    if (s !== H && U(s, t))
      return l[t] = 4, s[t];
    if (
      // global properties
      T = a.config.globalProperties, U(T, t)
    )
      return T[t];
  },
  set({ _: e }, t, s) {
    const { data: n, setupState: r, ctx: i } = e;
    return xs(r, t) ? (r[t] = s, !0) : n !== H && U(n, t) ? (n[t] = s, !0) : U(e.props, t) || t[0] === "$" && t.slice(1) in e ? !1 : (i[t] = s, !0);
  },
  has({
    _: { data: e, setupState: t, accessCache: s, ctx: n, appContext: r, propsOptions: i }
  }, l) {
    let o;
    return !!s[l] || e !== H && U(e, l) || xs(t, l) || (o = i[0]) && U(o, l) || U(n, l) || U(Ct, l) || U(r.config.globalProperties, l);
  },
  defineProperty(e, t, s) {
    return s.get != null ? e._.accessCache[t] = 0 : U(s, "value") && this.set(e, t, s.value, null), Reflect.defineProperty(e, t, s);
  }
};
function bn(e) {
  return R(e) ? e.reduce(
    (t, s) => (t[s] = null, t),
    {}
  ) : e;
}
let Ds = !0;
function ll(e) {
  const t = wr(e), s = e.proxy, n = e.ctx;
  Ds = !1, t.beforeCreate && jn(t.beforeCreate, e, "bc");
  const {
    // state
    data: r,
    computed: i,
    methods: l,
    watch: o,
    provide: a,
    inject: d,
    // lifecycle
    created: c,
    beforeMount: p,
    mounted: T,
    beforeUpdate: C,
    updated: M,
    activated: D,
    deactivated: ee,
    beforeDestroy: J,
    beforeUnmount: G,
    destroyed: Y,
    unmounted: O,
    render: X,
    renderTracked: Ue,
    renderTriggered: ve,
    errorCaptured: ke,
    serverPrefetch: Mt,
    // public API
    expose: $e,
    inheritAttrs: ht,
    // assets
    components: Nt,
    directives: Dt,
    filters: hs
  } = t;
  if (d && ol(d, n, null), l)
    for (const $ in l) {
      const L = l[$];
      I(L) && (n[$] = L.bind(s));
    }
  if (r) {
    const $ = r.call(s, s);
    W($) && (e.data = Qs($));
  }
  if (Ds = !0, i)
    for (const $ in i) {
      const L = i[$], qe = I(L) ? L.bind(s, s) : I(L.get) ? L.get.bind(s, s) : Pe, Ft = !I(L) && I(L.set) ? L.set.bind(s) : Pe, Ge = Gl({
        get: qe,
        set: Ft
      });
      Object.defineProperty(n, $, {
        enumerable: !0,
        configurable: !0,
        get: () => Ge.value,
        set: (_e) => Ge.value = _e
      });
    }
  if (o)
    for (const $ in o)
      _r(o[$], n, s, $);
  if (a) {
    const $ = I(a) ? a.call(s) : a;
    Reflect.ownKeys($).forEach((L) => {
      hl(L, $[L]);
    });
  }
  c && jn(c, e, "c");
  function ie($, L) {
    R(L) ? L.forEach((qe) => $(qe.bind(s))) : L && $(L.bind(s));
  }
  if (ie(qi, p), ie(Gi, T), ie(Yi, C), ie(zi, M), ie(Bi, D), ie(Wi, ee), ie(tl, ke), ie(el, Ue), ie(Qi, ve), ie(Xi, G), ie(vr, O), ie(Zi, Mt), R($e))
    if ($e.length) {
      const $ = e.exposed || (e.exposed = {});
      $e.forEach((L) => {
        Object.defineProperty($, L, {
          get: () => s[L],
          set: (qe) => s[L] = qe
        });
      });
    } else e.exposed || (e.exposed = {});
  X && e.render === Pe && (e.render = X), ht != null && (e.inheritAttrs = ht), Nt && (e.components = Nt), Dt && (e.directives = Dt), Mt && br(e);
}
function ol(e, t, s = Pe) {
  R(e) && (e = Fs(e));
  for (const n in e) {
    const r = e[n];
    let i;
    W(r) ? "default" in r ? i = Kt(
      r.from || n,
      r.default,
      !0
    ) : i = Kt(r.from || n) : i = Kt(r), re(i) ? Object.defineProperty(t, n, {
      enumerable: !0,
      configurable: !0,
      get: () => i.value,
      set: (l) => i.value = l
    }) : t[n] = i;
  }
}
function jn(e, t, s) {
  Re(
    R(e) ? e.map((n) => n.bind(t.proxy)) : e.bind(t.proxy),
    t,
    s
  );
}
function _r(e, t, s, n) {
  let r = n.includes(".") ? Fr(s, n) : () => s[n];
  if (z(e)) {
    const i = t[e];
    I(i) && _s(r, i);
  } else if (I(e))
    _s(r, e.bind(s));
  else if (W(e))
    if (R(e))
      e.forEach((i) => _r(i, t, s, n));
    else {
      const i = I(e.handler) ? e.handler.bind(s) : t[e.handler];
      I(i) && _s(r, i, e);
    }
}
function wr(e) {
  const t = e.type, { mixins: s, extends: n } = t, {
    mixins: r,
    optionsCache: i,
    config: { optionMergeStrategies: l }
  } = e.appContext, o = i.get(t);
  let a;
  return o ? a = o : !r.length && !s && !n ? a = t : (a = {}, r.length && r.forEach(
    (d) => Xt(a, d, l, !0)
  ), Xt(a, t, l)), W(t) && i.set(t, a), a;
}
function Xt(e, t, s, n = !1) {
  const { mixins: r, extends: i } = t;
  i && Xt(e, i, s, !0), r && r.forEach(
    (l) => Xt(e, l, s, !0)
  );
  for (const l in t)
    if (!(n && l === "expose")) {
      const o = al[l] || s && s[l];
      e[l] = o ? o(e[l], t[l]) : t[l];
    }
  return e;
}
const al = {
  data: xn,
  props: vn,
  emits: vn,
  // objects
  methods: xt,
  computed: xt,
  // lifecycle
  beforeCreate: le,
  created: le,
  beforeMount: le,
  mounted: le,
  beforeUpdate: le,
  updated: le,
  beforeDestroy: le,
  beforeUnmount: le,
  destroyed: le,
  unmounted: le,
  activated: le,
  deactivated: le,
  errorCaptured: le,
  serverPrefetch: le,
  // assets
  components: xt,
  directives: xt,
  // watch
  watch: fl,
  // provide / inject
  provide: xn,
  inject: ul
};
function xn(e, t) {
  return t ? e ? function() {
    return ue(
      I(e) ? e.call(this, this) : e,
      I(t) ? t.call(this, this) : t
    );
  } : t : e;
}
function ul(e, t) {
  return xt(Fs(e), Fs(t));
}
function Fs(e) {
  if (R(e)) {
    const t = {};
    for (let s = 0; s < e.length; s++)
      t[e[s]] = e[s];
    return t;
  }
  return e;
}
function le(e, t) {
  return e ? [...new Set([].concat(e, t))] : t;
}
function xt(e, t) {
  return e ? ue(/* @__PURE__ */ Object.create(null), e, t) : t;
}
function vn(e, t) {
  return e ? R(e) && R(t) ? [.../* @__PURE__ */ new Set([...e, ...t])] : ue(
    /* @__PURE__ */ Object.create(null),
    bn(e),
    bn(t ?? {})
  ) : t;
}
function fl(e, t) {
  if (!e) return t;
  if (!t) return e;
  const s = ue(/* @__PURE__ */ Object.create(null), e);
  for (const n in t)
    s[n] = le(e[n], t[n]);
  return s;
}
function Tr() {
  return {
    app: null,
    config: {
      isNativeTag: Yr,
      performance: !1,
      globalProperties: {},
      optionMergeStrategies: {},
      errorHandler: void 0,
      warnHandler: void 0,
      compilerOptions: {}
    },
    mixins: [],
    components: {},
    directives: {},
    provides: /* @__PURE__ */ Object.create(null),
    optionsCache: /* @__PURE__ */ new WeakMap(),
    propsCache: /* @__PURE__ */ new WeakMap(),
    emitsCache: /* @__PURE__ */ new WeakMap()
  };
}
let cl = 0;
function dl(e, t) {
  return function(n, r = null) {
    I(n) || (n = ue({}, n)), r != null && !W(r) && (r = null);
    const i = Tr(), l = /* @__PURE__ */ new WeakSet(), o = [];
    let a = !1;
    const d = i.app = {
      _uid: cl++,
      _component: n,
      _props: r,
      _container: null,
      _context: i,
      _instance: null,
      version: zl,
      get config() {
        return i.config;
      },
      set config(c) {
      },
      use(c, ...p) {
        return l.has(c) || (c && I(c.install) ? (l.add(c), c.install(d, ...p)) : I(c) && (l.add(c), c(d, ...p))), d;
      },
      mixin(c) {
        return i.mixins.includes(c) || i.mixins.push(c), d;
      },
      component(c, p) {
        return p ? (i.components[c] = p, d) : i.components[c];
      },
      directive(c, p) {
        return p ? (i.directives[c] = p, d) : i.directives[c];
      },
      mount(c, p, T) {
        if (!a) {
          const C = d._ceVNode || Q(n, r);
          return C.appContext = i, T === !0 ? T = "svg" : T === !1 && (T = void 0), e(C, c, T), a = !0, d._container = c, c.__vue_app__ = d, ds(C.component);
        }
      },
      onUnmount(c) {
        o.push(c);
      },
      unmount() {
        a && (Re(
          o,
          d._instance,
          16
        ), e(null, d._container), delete d._container.__vue_app__);
      },
      provide(c, p) {
        return i.provides[c] = p, d;
      },
      runWithContext(c) {
        const p = ct;
        ct = d;
        try {
          return c();
        } finally {
          ct = p;
        }
      }
    };
    return d;
  };
}
let ct = null;
function hl(e, t) {
  if (ne) {
    let s = ne.provides;
    const n = ne.parent && ne.parent.provides;
    n === s && (s = ne.provides = Object.create(n)), s[e] = t;
  }
}
function Kt(e, t, s = !1) {
  const n = ne || me;
  if (n || ct) {
    const r = ct ? ct._context.provides : n ? n.parent == null ? n.vnode.appContext && n.vnode.appContext.provides : n.parent.provides : void 0;
    if (r && e in r)
      return r[e];
    if (arguments.length > 1)
      return s && I(t) ? t.call(n && n.proxy) : t;
  }
}
const Cr = {}, Sr = () => Object.create(Cr), Ar = (e) => Object.getPrototypeOf(e) === Cr;
function pl(e, t, s, n = !1) {
  const r = {}, i = Sr();
  e.propsDefaults = /* @__PURE__ */ Object.create(null), Er(e, t, r, i);
  for (const l in e.propsOptions[0])
    l in r || (r[l] = void 0);
  s ? e.props = n ? r : Si(r) : e.type.props ? e.props = r : e.props = i, e.attrs = i;
}
function gl(e, t, s, n) {
  const {
    props: r,
    attrs: i,
    vnode: { patchFlag: l }
  } = e, o = F(r), [a] = e.propsOptions;
  let d = !1;
  if (
    // always force full diff in dev
    // - #1942 if hmr is enabled with sfc component
    // - vite#872 non-sfc component used by sfc component
    (n || l > 0) && !(l & 16)
  ) {
    if (l & 8) {
      const c = e.vnode.dynamicProps;
      for (let p = 0; p < c.length; p++) {
        let T = c[p];
        if (fs(e.emitsOptions, T))
          continue;
        const C = t[T];
        if (a)
          if (U(i, T))
            C !== i[T] && (i[T] = C, d = !0);
          else {
            const M = je(T);
            r[M] = Us(
              a,
              o,
              M,
              C,
              e,
              !1
            );
          }
        else
          C !== i[T] && (i[T] = C, d = !0);
      }
    }
  } else {
    Er(e, t, r, i) && (d = !0);
    let c;
    for (const p in o)
      (!t || // for camelCase
      !U(t, p) && // it's possible the original props was passed in as kebab-case
      // and converted to camelCase (#955)
      ((c = tt(p)) === p || !U(t, c))) && (a ? s && // for camelCase
      (s[p] !== void 0 || // for kebab-case
      s[c] !== void 0) && (r[p] = Us(
        a,
        o,
        p,
        void 0,
        e,
        !0
      )) : delete r[p]);
    if (i !== o)
      for (const p in i)
        (!t || !U(t, p)) && (delete i[p], d = !0);
  }
  d && Ne(e.attrs, "set", "");
}
function Er(e, t, s, n) {
  const [r, i] = e.propsOptions;
  let l = !1, o;
  if (t)
    for (let a in t) {
      if (vt(a))
        continue;
      const d = t[a];
      let c;
      r && U(r, c = je(a)) ? !i || !i.includes(c) ? s[c] = d : (o || (o = {}))[c] = d : fs(e.emitsOptions, a) || (!(a in n) || d !== n[a]) && (n[a] = d, l = !0);
    }
  if (i) {
    const a = F(s), d = o || H;
    for (let c = 0; c < i.length; c++) {
      const p = i[c];
      s[p] = Us(
        r,
        a,
        p,
        d[p],
        e,
        !U(d, p)
      );
    }
  }
  return l;
}
function Us(e, t, s, n, r, i) {
  const l = e[s];
  if (l != null) {
    const o = U(l, "default");
    if (o && n === void 0) {
      const a = l.default;
      if (l.type !== Function && !l.skipFactory && I(a)) {
        const { propsDefaults: d } = r;
        if (s in d)
          n = d[s];
        else {
          const c = It(r);
          n = d[s] = a.call(
            null,
            t
          ), c();
        }
      } else
        n = a;
      r.ce && r.ce._setProp(s, n);
    }
    l[
      0
      /* shouldCast */
    ] && (i && !o ? n = !1 : l[
      1
      /* shouldCastTrue */
    ] && (n === "" || n === tt(s)) && (n = !0));
  }
  return n;
}
const ml = /* @__PURE__ */ new WeakMap();
function Vr(e, t, s = !1) {
  const n = s ? ml : t.propsCache, r = n.get(e);
  if (r)
    return r;
  const i = e.props, l = {}, o = [];
  let a = !1;
  if (!I(e)) {
    const c = (p) => {
      a = !0;
      const [T, C] = Vr(p, t, !0);
      ue(l, T), C && o.push(...C);
    };
    !s && t.mixins.length && t.mixins.forEach(c), e.extends && c(e.extends), e.mixins && e.mixins.forEach(c);
  }
  if (!i && !a)
    return W(e) && n.set(e, ot), ot;
  if (R(i))
    for (let c = 0; c < i.length; c++) {
      const p = je(i[c]);
      _n(p) && (l[p] = H);
    }
  else if (i)
    for (const c in i) {
      const p = je(c);
      if (_n(p)) {
        const T = i[c], C = l[p] = R(T) || I(T) ? { type: T } : ue({}, T), M = C.type;
        let D = !1, ee = !0;
        if (R(M))
          for (let J = 0; J < M.length; ++J) {
            const G = M[J], Y = I(G) && G.name;
            if (Y === "Boolean") {
              D = !0;
              break;
            } else Y === "String" && (ee = !1);
          }
        else
          D = I(M) && M.name === "Boolean";
        C[
          0
          /* shouldCast */
        ] = D, C[
          1
          /* shouldCastTrue */
        ] = ee, (D || U(C, "default")) && o.push(p);
      }
    }
  const d = [l, o];
  return W(e) && n.set(e, d), d;
}
function _n(e) {
  return e[0] !== "$" && !vt(e);
}
const Or = (e) => e[0] === "_" || e === "$stable", rn = (e) => R(e) ? e.map(Ve) : [Ve(e)], yl = (e, t, s) => {
  if (t._n)
    return t;
  const n = Hi((...r) => rn(t(...r)), s);
  return n._c = !1, n;
}, Pr = (e, t, s) => {
  const n = e._ctx;
  for (const r in e) {
    if (Or(r)) continue;
    const i = e[r];
    if (I(i))
      t[r] = yl(r, i, n);
    else if (i != null) {
      const l = rn(i);
      t[r] = () => l;
    }
  }
}, Rr = (e, t) => {
  const s = rn(t);
  e.slots.default = () => s;
}, Ir = (e, t, s) => {
  for (const n in t)
    (s || n !== "_") && (e[n] = t[n]);
}, bl = (e, t, s) => {
  const n = e.slots = Sr();
  if (e.vnode.shapeFlag & 32) {
    const r = t._;
    r ? (Ir(n, t, s), s && $n(n, "_", r, !0)) : Pr(t, n);
  } else t && Rr(e, t);
}, jl = (e, t, s) => {
  const { vnode: n, slots: r } = e;
  let i = !0, l = H;
  if (n.shapeFlag & 32) {
    const o = t._;
    o ? s && o === 1 ? i = !1 : Ir(r, t, s) : (i = !t.$stable, Pr(t, r)), l = t;
  } else t && (Rr(e, t), l = { default: 1 });
  if (i)
    for (const o in r)
      !Or(o) && l[o] == null && delete r[o];
}, pe = Il;
function xl(e) {
  return vl(e);
}
function vl(e, t) {
  const s = ls();
  s.__VUE__ = !0;
  const {
    insert: n,
    remove: r,
    patchProp: i,
    createElement: l,
    createText: o,
    createComment: a,
    setText: d,
    setElementText: c,
    parentNode: p,
    nextSibling: T,
    setScopeId: C = Pe,
    insertStaticContent: M
  } = e, D = (u, f, h, y = null, g = null, m = null, _ = void 0, v = null, x = !!f.dynamicChildren) => {
    if (u === f)
      return;
    u && !bt(u, f) && (y = Ut(u), _e(u, g, m, !0), u = null), f.patchFlag === -2 && (x = !1, f.dynamicChildren = null);
    const { type: b, ref: A, shapeFlag: w } = f;
    switch (b) {
      case cs:
        ee(u, f, h, y);
        break;
      case et:
        J(u, f, h, y);
        break;
      case Bt:
        u == null && G(f, h, y, _);
        break;
      case Z:
        Nt(
          u,
          f,
          h,
          y,
          g,
          m,
          _,
          v,
          x
        );
        break;
      default:
        w & 1 ? X(
          u,
          f,
          h,
          y,
          g,
          m,
          _,
          v,
          x
        ) : w & 6 ? Dt(
          u,
          f,
          h,
          y,
          g,
          m,
          _,
          v,
          x
        ) : (w & 64 || w & 128) && b.process(
          u,
          f,
          h,
          y,
          g,
          m,
          _,
          v,
          x,
          gt
        );
    }
    A != null && g && zt(A, u && u.ref, m, f || u, !f);
  }, ee = (u, f, h, y) => {
    if (u == null)
      n(
        f.el = o(f.children),
        h,
        y
      );
    else {
      const g = f.el = u.el;
      f.children !== u.children && d(g, f.children);
    }
  }, J = (u, f, h, y) => {
    u == null ? n(
      f.el = a(f.children || ""),
      h,
      y
    ) : f.el = u.el;
  }, G = (u, f, h, y) => {
    [u.el, u.anchor] = M(
      u.children,
      f,
      h,
      y,
      u.el,
      u.anchor
    );
  }, Y = ({ el: u, anchor: f }, h, y) => {
    let g;
    for (; u && u !== f; )
      g = T(u), n(u, h, y), u = g;
    n(f, h, y);
  }, O = ({ el: u, anchor: f }) => {
    let h;
    for (; u && u !== f; )
      h = T(u), r(u), u = h;
    r(f);
  }, X = (u, f, h, y, g, m, _, v, x) => {
    f.type === "svg" ? _ = "svg" : f.type === "math" && (_ = "mathml"), u == null ? Ue(
      f,
      h,
      y,
      g,
      m,
      _,
      v,
      x
    ) : Mt(
      u,
      f,
      g,
      m,
      _,
      v,
      x
    );
  }, Ue = (u, f, h, y, g, m, _, v) => {
    let x, b;
    const { props: A, shapeFlag: w, transition: S, dirs: P } = u;
    if (x = u.el = l(
      u.type,
      m,
      A && A.is,
      A
    ), w & 8 ? c(x, u.children) : w & 16 && ke(
      u.children,
      x,
      null,
      y,
      g,
      vs(u, m),
      _,
      v
    ), P && Ye(u, null, y, "created"), ve(x, u, u.scopeId, _, y), A) {
      for (const K in A)
        K !== "value" && !vt(K) && i(x, K, null, A[K], m, y);
      "value" in A && i(x, "value", null, A.value, m), (b = A.onVnodeBeforeMount) && Se(b, y, u);
    }
    P && Ye(u, null, y, "beforeMount");
    const N = _l(g, S);
    N && S.beforeEnter(x), n(x, f, h), ((b = A && A.onVnodeMounted) || N || P) && pe(() => {
      b && Se(b, y, u), N && S.enter(x), P && Ye(u, null, y, "mounted");
    }, g);
  }, ve = (u, f, h, y, g) => {
    if (h && C(u, h), y)
      for (let m = 0; m < y.length; m++)
        C(u, y[m]);
    if (g) {
      let m = g.subTree;
      if (f === m || kr(m.type) && (m.ssContent === f || m.ssFallback === f)) {
        const _ = g.vnode;
        ve(
          u,
          _,
          _.scopeId,
          _.slotScopeIds,
          g.parent
        );
      }
    }
  }, ke = (u, f, h, y, g, m, _, v, x = 0) => {
    for (let b = x; b < u.length; b++) {
      const A = u[b] = v ? He(u[b]) : Ve(u[b]);
      D(
        null,
        A,
        f,
        h,
        y,
        g,
        m,
        _,
        v
      );
    }
  }, Mt = (u, f, h, y, g, m, _) => {
    const v = f.el = u.el;
    let { patchFlag: x, dynamicChildren: b, dirs: A } = f;
    x |= u.patchFlag & 16;
    const w = u.props || H, S = f.props || H;
    let P;
    if (h && ze(h, !1), (P = S.onVnodeBeforeUpdate) && Se(P, h, f, u), A && Ye(f, u, h, "beforeUpdate"), h && ze(h, !0), (w.innerHTML && S.innerHTML == null || w.textContent && S.textContent == null) && c(v, ""), b ? $e(
      u.dynamicChildren,
      b,
      v,
      h,
      y,
      vs(f, g),
      m
    ) : _ || L(
      u,
      f,
      v,
      null,
      h,
      y,
      vs(f, g),
      m,
      !1
    ), x > 0) {
      if (x & 16)
        ht(v, w, S, h, g);
      else if (x & 2 && w.class !== S.class && i(v, "class", null, S.class, g), x & 4 && i(v, "style", w.style, S.style, g), x & 8) {
        const N = f.dynamicProps;
        for (let K = 0; K < N.length; K++) {
          const k = N[K], de = w[k], fe = S[k];
          (fe !== de || k === "value") && i(v, k, de, fe, g, h);
        }
      }
      x & 1 && u.children !== f.children && c(v, f.children);
    } else !_ && b == null && ht(v, w, S, h, g);
    ((P = S.onVnodeUpdated) || A) && pe(() => {
      P && Se(P, h, f, u), A && Ye(f, u, h, "updated");
    }, y);
  }, $e = (u, f, h, y, g, m, _) => {
    for (let v = 0; v < f.length; v++) {
      const x = u[v], b = f[v], A = (
        // oldVNode may be an errored async setup() component inside Suspense
        // which will not have a mounted element
        x.el && // - In the case of a Fragment, we need to provide the actual parent
        // of the Fragment itself so it can move its children.
        (x.type === Z || // - In the case of different nodes, there is going to be a replacement
        // which also requires the correct parent container
        !bt(x, b) || // - In the case of a component, it could contain anything.
        x.shapeFlag & 70) ? p(x.el) : (
          // In other cases, the parent container is not actually used so we
          // just pass the block element here to avoid a DOM parentNode call.
          h
        )
      );
      D(
        x,
        b,
        A,
        null,
        y,
        g,
        m,
        _,
        !0
      );
    }
  }, ht = (u, f, h, y, g) => {
    if (f !== h) {
      if (f !== H)
        for (const m in f)
          !vt(m) && !(m in h) && i(
            u,
            m,
            f[m],
            null,
            g,
            y
          );
      for (const m in h) {
        if (vt(m)) continue;
        const _ = h[m], v = f[m];
        _ !== v && m !== "value" && i(u, m, v, _, g, y);
      }
      "value" in h && i(u, "value", f.value, h.value, g);
    }
  }, Nt = (u, f, h, y, g, m, _, v, x) => {
    const b = f.el = u ? u.el : o(""), A = f.anchor = u ? u.anchor : o("");
    let { patchFlag: w, dynamicChildren: S, slotScopeIds: P } = f;
    P && (v = v ? v.concat(P) : P), u == null ? (n(b, h, y), n(A, h, y), ke(
      // #10007
      // such fragment like `<></>` will be compiled into
      // a fragment which doesn't have a children.
      // In this case fallback to an empty array
      f.children || [],
      h,
      A,
      g,
      m,
      _,
      v,
      x
    )) : w > 0 && w & 64 && S && // #2715 the previous fragment could've been a BAILed one as a result
    // of renderSlot() with no valid children
    u.dynamicChildren ? ($e(
      u.dynamicChildren,
      S,
      h,
      g,
      m,
      _,
      v
    ), // #2080 if the stable fragment has a key, it's a <template v-for> that may
    //  get moved around. Make sure all root level vnodes inherit el.
    // #2134 or if it's a component root, it may also get moved around
    // as the component is being moved.
    (f.key != null || g && f === g.subTree) && Mr(
      u,
      f,
      !0
      /* shallow */
    )) : L(
      u,
      f,
      h,
      A,
      g,
      m,
      _,
      v,
      x
    );
  }, Dt = (u, f, h, y, g, m, _, v, x) => {
    f.slotScopeIds = v, u == null ? f.shapeFlag & 512 ? g.ctx.activate(
      f,
      h,
      y,
      _,
      x
    ) : hs(
      f,
      h,
      y,
      g,
      m,
      _,
      x
    ) : an(u, f, x);
  }, hs = (u, f, h, y, g, m, _) => {
    const v = u.component = Hl(
      u,
      y,
      g
    );
    if (jr(u) && (v.ctx.renderer = gt), Ll(v, !1, _), v.asyncDep) {
      if (g && g.registerDep(v, ie, _), !u.el) {
        const x = v.subTree = Q(et);
        J(null, x, f, h);
      }
    } else
      ie(
        v,
        u,
        f,
        h,
        g,
        m,
        _
      );
  }, an = (u, f, h) => {
    const y = f.component = u.component;
    if (Pl(u, f, h))
      if (y.asyncDep && !y.asyncResolved) {
        $(y, f, h);
        return;
      } else
        y.next = f, y.update();
    else
      f.el = u.el, y.vnode = f;
  }, ie = (u, f, h, y, g, m, _) => {
    const v = () => {
      if (u.isMounted) {
        let { next: w, bu: S, u: P, parent: N, vnode: K } = u;
        {
          const Te = Nr(u);
          if (Te) {
            w && (w.el = K.el, $(u, w, _)), Te.asyncDep.then(() => {
              u.isUnmounted || v();
            });
            return;
          }
        }
        let k = w, de;
        ze(u, !1), w ? (w.el = K.el, $(u, w, _)) : w = K, S && Lt(S), (de = w.props && w.props.onVnodeBeforeUpdate) && Se(de, N, w, K), ze(u, !0);
        const fe = Tn(u), we = u.subTree;
        u.subTree = fe, D(
          we,
          fe,
          // parent may have changed if it's in a teleport
          p(we.el),
          // anchor may have changed if it's in a fragment
          Ut(we),
          u,
          g,
          m
        ), w.el = fe.el, k === null && Rl(u, fe.el), P && pe(P, g), (de = w.props && w.props.onVnodeUpdated) && pe(
          () => Se(de, N, w, K),
          g
        );
      } else {
        let w;
        const { el: S, props: P } = f, { bm: N, m: K, parent: k, root: de, type: fe } = u, we = Tt(f);
        ze(u, !1), N && Lt(N), !we && (w = P && P.onVnodeBeforeMount) && Se(w, k, f), ze(u, !0);
        {
          de.ce && de.ce._injectChildStyle(fe);
          const Te = u.subTree = Tn(u);
          D(
            null,
            Te,
            h,
            y,
            u,
            g,
            m
          ), f.el = Te.el;
        }
        if (K && pe(K, g), !we && (w = P && P.onVnodeMounted)) {
          const Te = f;
          pe(
            () => Se(w, k, Te),
            g
          );
        }
        (f.shapeFlag & 256 || k && Tt(k.vnode) && k.vnode.shapeFlag & 256) && u.a && pe(u.a, g), u.isMounted = !0, f = h = y = null;
      }
    };
    u.scope.on();
    const x = u.effect = new zn(v);
    u.scope.off();
    const b = u.update = x.run.bind(x), A = u.job = x.runIfDirty.bind(x);
    A.i = u, A.id = u.uid, x.scheduler = () => sn(A), ze(u, !0), b();
  }, $ = (u, f, h) => {
    f.component = u;
    const y = u.vnode.props;
    u.vnode = f, u.next = null, gl(u, f.props, y, h), jl(u, f.children, h), Be(), mn(u), We();
  }, L = (u, f, h, y, g, m, _, v, x = !1) => {
    const b = u && u.children, A = u ? u.shapeFlag : 0, w = f.children, { patchFlag: S, shapeFlag: P } = f;
    if (S > 0) {
      if (S & 128) {
        Ft(
          b,
          w,
          h,
          y,
          g,
          m,
          _,
          v,
          x
        );
        return;
      } else if (S & 256) {
        qe(
          b,
          w,
          h,
          y,
          g,
          m,
          _,
          v,
          x
        );
        return;
      }
    }
    P & 8 ? (A & 16 && pt(b, g, m), w !== b && c(h, w)) : A & 16 ? P & 16 ? Ft(
      b,
      w,
      h,
      y,
      g,
      m,
      _,
      v,
      x
    ) : pt(b, g, m, !0) : (A & 8 && c(h, ""), P & 16 && ke(
      w,
      h,
      y,
      g,
      m,
      _,
      v,
      x
    ));
  }, qe = (u, f, h, y, g, m, _, v, x) => {
    u = u || ot, f = f || ot;
    const b = u.length, A = f.length, w = Math.min(b, A);
    let S;
    for (S = 0; S < w; S++) {
      const P = f[S] = x ? He(f[S]) : Ve(f[S]);
      D(
        u[S],
        P,
        h,
        null,
        g,
        m,
        _,
        v,
        x
      );
    }
    b > A ? pt(
      u,
      g,
      m,
      !0,
      !1,
      w
    ) : ke(
      f,
      h,
      y,
      g,
      m,
      _,
      v,
      x,
      w
    );
  }, Ft = (u, f, h, y, g, m, _, v, x) => {
    let b = 0;
    const A = f.length;
    let w = u.length - 1, S = A - 1;
    for (; b <= w && b <= S; ) {
      const P = u[b], N = f[b] = x ? He(f[b]) : Ve(f[b]);
      if (bt(P, N))
        D(
          P,
          N,
          h,
          null,
          g,
          m,
          _,
          v,
          x
        );
      else
        break;
      b++;
    }
    for (; b <= w && b <= S; ) {
      const P = u[w], N = f[S] = x ? He(f[S]) : Ve(f[S]);
      if (bt(P, N))
        D(
          P,
          N,
          h,
          null,
          g,
          m,
          _,
          v,
          x
        );
      else
        break;
      w--, S--;
    }
    if (b > w) {
      if (b <= S) {
        const P = S + 1, N = P < A ? f[P].el : y;
        for (; b <= S; )
          D(
            null,
            f[b] = x ? He(f[b]) : Ve(f[b]),
            h,
            N,
            g,
            m,
            _,
            v,
            x
          ), b++;
      }
    } else if (b > S)
      for (; b <= w; )
        _e(u[b], g, m, !0), b++;
    else {
      const P = b, N = b, K = /* @__PURE__ */ new Map();
      for (b = N; b <= S; b++) {
        const he = f[b] = x ? He(f[b]) : Ve(f[b]);
        he.key != null && K.set(he.key, b);
      }
      let k, de = 0;
      const fe = S - N + 1;
      let we = !1, Te = 0;
      const mt = new Array(fe);
      for (b = 0; b < fe; b++) mt[b] = 0;
      for (b = P; b <= w; b++) {
        const he = u[b];
        if (de >= fe) {
          _e(he, g, m, !0);
          continue;
        }
        let Ce;
        if (he.key != null)
          Ce = K.get(he.key);
        else
          for (k = N; k <= S; k++)
            if (mt[k - N] === 0 && bt(he, f[k])) {
              Ce = k;
              break;
            }
        Ce === void 0 ? _e(he, g, m, !0) : (mt[Ce - N] = b + 1, Ce >= Te ? Te = Ce : we = !0, D(
          he,
          f[Ce],
          h,
          null,
          g,
          m,
          _,
          v,
          x
        ), de++);
      }
      const cn = we ? wl(mt) : ot;
      for (k = cn.length - 1, b = fe - 1; b >= 0; b--) {
        const he = N + b, Ce = f[he], dn = he + 1 < A ? f[he + 1].el : y;
        mt[b] === 0 ? D(
          null,
          Ce,
          h,
          dn,
          g,
          m,
          _,
          v,
          x
        ) : we && (k < 0 || b !== cn[k] ? Ge(Ce, h, dn, 2) : k--);
      }
    }
  }, Ge = (u, f, h, y, g = null) => {
    const { el: m, type: _, transition: v, children: x, shapeFlag: b } = u;
    if (b & 6) {
      Ge(u.component.subTree, f, h, y);
      return;
    }
    if (b & 128) {
      u.suspense.move(f, h, y);
      return;
    }
    if (b & 64) {
      _.move(u, f, h, gt);
      return;
    }
    if (_ === Z) {
      n(m, f, h);
      for (let w = 0; w < x.length; w++)
        Ge(x[w], f, h, y);
      n(u.anchor, f, h);
      return;
    }
    if (_ === Bt) {
      Y(u, f, h);
      return;
    }
    if (y !== 2 && b & 1 && v)
      if (y === 0)
        v.beforeEnter(m), n(m, f, h), pe(() => v.enter(m), g);
      else {
        const { leave: w, delayLeave: S, afterLeave: P } = v, N = () => n(m, f, h), K = () => {
          w(m, () => {
            N(), P && P();
          });
        };
        S ? S(m, N, K) : K();
      }
    else
      n(m, f, h);
  }, _e = (u, f, h, y = !1, g = !1) => {
    const {
      type: m,
      props: _,
      ref: v,
      children: x,
      dynamicChildren: b,
      shapeFlag: A,
      patchFlag: w,
      dirs: S,
      cacheIndex: P
    } = u;
    if (w === -2 && (g = !1), v != null && zt(v, null, h, u, !0), P != null && (f.renderCache[P] = void 0), A & 256) {
      f.ctx.deactivate(u);
      return;
    }
    const N = A & 1 && S, K = !Tt(u);
    let k;
    if (K && (k = _ && _.onVnodeBeforeUnmount) && Se(k, f, u), A & 6)
      Gr(u.component, h, y);
    else {
      if (A & 128) {
        u.suspense.unmount(h, y);
        return;
      }
      N && Ye(u, null, f, "beforeUnmount"), A & 64 ? u.type.remove(
        u,
        f,
        h,
        gt,
        y
      ) : b && // #5154
      // when v-once is used inside a block, setBlockTracking(-1) marks the
      // parent block with hasOnce: true
      // so that it doesn't take the fast path during unmount - otherwise
      // components nested in v-once are never unmounted.
      !b.hasOnce && // #1153: fast path should not be taken for non-stable (v-for) fragments
      (m !== Z || w > 0 && w & 64) ? pt(
        b,
        f,
        h,
        !1,
        !0
      ) : (m === Z && w & 384 || !g && A & 16) && pt(x, f, h), y && un(u);
    }
    (K && (k = _ && _.onVnodeUnmounted) || N) && pe(() => {
      k && Se(k, f, u), N && Ye(u, null, f, "unmounted");
    }, h);
  }, un = (u) => {
    const { type: f, el: h, anchor: y, transition: g } = u;
    if (f === Z) {
      qr(h, y);
      return;
    }
    if (f === Bt) {
      O(u);
      return;
    }
    const m = () => {
      r(h), g && !g.persisted && g.afterLeave && g.afterLeave();
    };
    if (u.shapeFlag & 1 && g && !g.persisted) {
      const { leave: _, delayLeave: v } = g, x = () => _(h, m);
      v ? v(u.el, m, x) : x();
    } else
      m();
  }, qr = (u, f) => {
    let h;
    for (; u !== f; )
      h = T(u), r(u), u = h;
    r(f);
  }, Gr = (u, f, h) => {
    const { bum: y, scope: g, job: m, subTree: _, um: v, m: x, a: b } = u;
    wn(x), wn(b), y && Lt(y), g.stop(), m && (m.flags |= 8, _e(_, u, f, h)), v && pe(v, f), pe(() => {
      u.isUnmounted = !0;
    }, f), f && f.pendingBranch && !f.isUnmounted && u.asyncDep && !u.asyncResolved && u.suspenseId === f.pendingId && (f.deps--, f.deps === 0 && f.resolve());
  }, pt = (u, f, h, y = !1, g = !1, m = 0) => {
    for (let _ = m; _ < u.length; _++)
      _e(u[_], f, h, y, g);
  }, Ut = (u) => {
    if (u.shapeFlag & 6)
      return Ut(u.component.subTree);
    if (u.shapeFlag & 128)
      return u.suspense.next();
    const f = T(u.anchor || u.el), h = f && f[Li];
    return h ? T(h) : f;
  };
  let ps = !1;
  const fn = (u, f, h) => {
    u == null ? f._vnode && _e(f._vnode, null, null, !0) : D(
      f._vnode || null,
      u,
      f,
      null,
      null,
      null,
      h
    ), f._vnode = u, ps || (ps = !0, mn(), gr(), ps = !1);
  }, gt = {
    p: D,
    um: _e,
    m: Ge,
    r: un,
    mt: hs,
    mc: ke,
    pc: L,
    pbc: $e,
    n: Ut,
    o: e
  };
  return {
    render: fn,
    hydrate: void 0,
    createApp: dl(fn)
  };
}
function vs({ type: e, props: t }, s) {
  return s === "svg" && e === "foreignObject" || s === "mathml" && e === "annotation-xml" && t && t.encoding && t.encoding.includes("html") ? void 0 : s;
}
function ze({ effect: e, job: t }, s) {
  s ? (e.flags |= 32, t.flags |= 4) : (e.flags &= -33, t.flags &= -5);
}
function _l(e, t) {
  return (!e || e && !e.pendingBranch) && t && !t.persisted;
}
function Mr(e, t, s = !1) {
  const n = e.children, r = t.children;
  if (R(n) && R(r))
    for (let i = 0; i < n.length; i++) {
      const l = n[i];
      let o = r[i];
      o.shapeFlag & 1 && !o.dynamicChildren && ((o.patchFlag <= 0 || o.patchFlag === 32) && (o = r[i] = He(r[i]), o.el = l.el), !s && o.patchFlag !== -2 && Mr(l, o)), o.type === cs && (o.el = l.el);
    }
}
function wl(e) {
  const t = e.slice(), s = [0];
  let n, r, i, l, o;
  const a = e.length;
  for (n = 0; n < a; n++) {
    const d = e[n];
    if (d !== 0) {
      if (r = s[s.length - 1], e[r] < d) {
        t[n] = r, s.push(n);
        continue;
      }
      for (i = 0, l = s.length - 1; i < l; )
        o = i + l >> 1, e[s[o]] < d ? i = o + 1 : l = o;
      d < e[s[i]] && (i > 0 && (t[n] = s[i - 1]), s[i] = n);
    }
  }
  for (i = s.length, l = s[i - 1]; i-- > 0; )
    s[i] = l, l = t[l];
  return s;
}
function Nr(e) {
  const t = e.subTree.component;
  if (t)
    return t.asyncDep && !t.asyncResolved ? t : Nr(t);
}
function wn(e) {
  if (e)
    for (let t = 0; t < e.length; t++)
      e[t].flags |= 8;
}
const Tl = Symbol.for("v-scx"), Cl = () => Kt(Tl);
function _s(e, t, s) {
  return Dr(e, t, s);
}
function Dr(e, t, s = H) {
  const { immediate: n, deep: r, flush: i, once: l } = s, o = ue({}, s), a = t && n || !t && i !== "post";
  let d;
  if (Pt) {
    if (i === "sync") {
      const C = Cl();
      d = C.__watcherHandles || (C.__watcherHandles = []);
    } else if (!a) {
      const C = () => {
      };
      return C.stop = Pe, C.resume = Pe, C.pause = Pe, C;
    }
  }
  const c = ne;
  o.call = (C, M, D) => Re(C, c, M, D);
  let p = !1;
  i === "post" ? o.scheduler = (C) => {
    pe(C, c && c.suspense);
  } : i !== "sync" && (p = !0, o.scheduler = (C, M) => {
    M ? C() : sn(C);
  }), o.augmentJob = (C) => {
    t && (C.flags |= 4), p && (C.flags |= 2, c && (C.id = c.uid, C.i = c));
  };
  const T = Di(e, t, o);
  return Pt && (d ? d.push(T) : a && T()), T;
}
function Sl(e, t, s) {
  const n = this.proxy, r = z(e) ? e.includes(".") ? Fr(n, e) : () => n[e] : e.bind(n, n);
  let i;
  I(t) ? i = t : (i = t.handler, s = t);
  const l = It(this), o = Dr(r, i.bind(n), s);
  return l(), o;
}
function Fr(e, t) {
  const s = t.split(".");
  return () => {
    let n = e;
    for (let r = 0; r < s.length && n; r++)
      n = n[s[r]];
    return n;
  };
}
const Al = (e, t) => t === "modelValue" || t === "model-value" ? e.modelModifiers : e[`${t}Modifiers`] || e[`${je(t)}Modifiers`] || e[`${tt(t)}Modifiers`];
function El(e, t, ...s) {
  if (e.isUnmounted) return;
  const n = e.vnode.props || H;
  let r = s;
  const i = t.startsWith("update:"), l = i && Al(n, t.slice(7));
  l && (l.trim && (r = s.map((c) => z(c) ? c.trim() : c)), l.number && (r = s.map(As)));
  let o, a = n[o = gs(t)] || // also try camelCase event handler (#2249)
  n[o = gs(je(t))];
  !a && i && (a = n[o = gs(tt(t))]), a && Re(
    a,
    e,
    6,
    r
  );
  const d = n[o + "Once"];
  if (d) {
    if (!e.emitted)
      e.emitted = {};
    else if (e.emitted[o])
      return;
    e.emitted[o] = !0, Re(
      d,
      e,
      6,
      r
    );
  }
}
function Ur(e, t, s = !1) {
  const n = t.emitsCache, r = n.get(e);
  if (r !== void 0)
    return r;
  const i = e.emits;
  let l = {}, o = !1;
  if (!I(e)) {
    const a = (d) => {
      const c = Ur(d, t, !0);
      c && (o = !0, ue(l, c));
    };
    !s && t.mixins.length && t.mixins.forEach(a), e.extends && a(e.extends), e.mixins && e.mixins.forEach(a);
  }
  return !i && !o ? (W(e) && n.set(e, null), null) : (R(i) ? i.forEach((a) => l[a] = null) : ue(l, i), W(e) && n.set(e, l), l);
}
function fs(e, t) {
  return !e || !ss(t) ? !1 : (t = t.slice(2).replace(/Once$/, ""), U(e, t[0].toLowerCase() + t.slice(1)) || U(e, tt(t)) || U(e, t));
}
function Tn(e) {
  const {
    type: t,
    vnode: s,
    proxy: n,
    withProxy: r,
    propsOptions: [i],
    slots: l,
    attrs: o,
    emit: a,
    render: d,
    renderCache: c,
    props: p,
    data: T,
    setupState: C,
    ctx: M,
    inheritAttrs: D
  } = e, ee = Yt(e);
  let J, G;
  try {
    if (s.shapeFlag & 4) {
      const O = r || n, X = O;
      J = Ve(
        d.call(
          X,
          O,
          c,
          p,
          C,
          T,
          M
        )
      ), G = o;
    } else {
      const O = t;
      J = Ve(
        O.length > 1 ? O(
          p,
          { attrs: o, slots: l, emit: a }
        ) : O(
          p,
          null
        )
      ), G = t.props ? o : Vl(o);
    }
  } catch (O) {
    St.length = 0, as(O, e, 1), J = Q(et);
  }
  let Y = J;
  if (G && D !== !1) {
    const O = Object.keys(G), { shapeFlag: X } = Y;
    O.length && X & 7 && (i && O.some(Bs) && (G = Ol(
      G,
      i
    )), Y = dt(Y, G, !1, !0));
  }
  return s.dirs && (Y = dt(Y, null, !1, !0), Y.dirs = Y.dirs ? Y.dirs.concat(s.dirs) : s.dirs), s.transition && nn(Y, s.transition), J = Y, Yt(ee), J;
}
const Vl = (e) => {
  let t;
  for (const s in e)
    (s === "class" || s === "style" || ss(s)) && ((t || (t = {}))[s] = e[s]);
  return t;
}, Ol = (e, t) => {
  const s = {};
  for (const n in e)
    (!Bs(n) || !(n.slice(9) in t)) && (s[n] = e[n]);
  return s;
};
function Pl(e, t, s) {
  const { props: n, children: r, component: i } = e, { props: l, children: o, patchFlag: a } = t, d = i.emitsOptions;
  if (t.dirs || t.transition)
    return !0;
  if (s && a >= 0) {
    if (a & 1024)
      return !0;
    if (a & 16)
      return n ? Cn(n, l, d) : !!l;
    if (a & 8) {
      const c = t.dynamicProps;
      for (let p = 0; p < c.length; p++) {
        const T = c[p];
        if (l[T] !== n[T] && !fs(d, T))
          return !0;
      }
    }
  } else
    return (r || o) && (!o || !o.$stable) ? !0 : n === l ? !1 : n ? l ? Cn(n, l, d) : !0 : !!l;
  return !1;
}
function Cn(e, t, s) {
  const n = Object.keys(t);
  if (n.length !== Object.keys(e).length)
    return !0;
  for (let r = 0; r < n.length; r++) {
    const i = n[r];
    if (t[i] !== e[i] && !fs(s, i))
      return !0;
  }
  return !1;
}
function Rl({ vnode: e, parent: t }, s) {
  for (; t; ) {
    const n = t.subTree;
    if (n.suspense && n.suspense.activeBranch === e && (n.el = e.el), n === e)
      (e = t.vnode).el = s, t = t.parent;
    else
      break;
  }
}
const kr = (e) => e.__isSuspense;
function Il(e, t) {
  t && t.pendingBranch ? R(e) ? t.effects.push(...e) : t.effects.push(e) : Ji(e);
}
const Z = Symbol.for("v-fgt"), cs = Symbol.for("v-txt"), et = Symbol.for("v-cmt"), Bt = Symbol.for("v-stc"), St = [];
let ye = null;
function E(e = !1) {
  St.push(ye = e ? null : []);
}
function Ml() {
  St.pop(), ye = St[St.length - 1] || null;
}
let Ot = 1;
function Sn(e, t = !1) {
  Ot += e, e < 0 && ye && t && (ye.hasOnce = !0);
}
function Jr(e) {
  return e.dynamicChildren = Ot > 0 ? ye || ot : null, Ml(), Ot > 0 && ye && ye.push(e), e;
}
function V(e, t, s, n, r, i) {
  return Jr(
    j(
      e,
      t,
      s,
      n,
      r,
      i,
      !0
    )
  );
}
function Nl(e, t, s, n, r) {
  return Jr(
    Q(
      e,
      t,
      s,
      n,
      r,
      !0
    )
  );
}
function Zt(e) {
  return e ? e.__v_isVNode === !0 : !1;
}
function bt(e, t) {
  return e.type === t.type && e.key === t.key;
}
const Hr = ({ key: e }) => e ?? null, Wt = ({
  ref: e,
  ref_key: t,
  ref_for: s
}) => (typeof e == "number" && (e = "" + e), e != null ? z(e) || re(e) || I(e) ? { i: me, r: e, k: t, f: !!s } : e : null);
function j(e, t = null, s = null, n = 0, r = null, i = e === Z ? 0 : 1, l = !1, o = !1) {
  const a = {
    __v_isVNode: !0,
    __v_skip: !0,
    type: e,
    props: t,
    key: t && Hr(t),
    ref: t && Wt(t),
    scopeId: yr,
    slotScopeIds: null,
    children: s,
    component: null,
    suspense: null,
    ssContent: null,
    ssFallback: null,
    dirs: null,
    transition: null,
    el: null,
    anchor: null,
    target: null,
    targetStart: null,
    targetAnchor: null,
    staticCount: 0,
    shapeFlag: i,
    patchFlag: n,
    dynamicProps: r,
    dynamicChildren: null,
    appContext: null,
    ctx: me
  };
  return o ? (ln(a, s), i & 128 && e.normalize(a)) : s && (a.shapeFlag |= z(s) ? 8 : 16), Ot > 0 && // avoid a block node from tracking itself
  !l && // has current parent block
  ye && // presence of a patch flag indicates this node needs patching on updates.
  // component nodes also should always be patched, because even if the
  // component doesn't need to update, it needs to persist the instance on to
  // the next vnode so that it can be properly unmounted later.
  (a.patchFlag > 0 || i & 6) && // the EVENTS flag is only for hydration and if it is the only flag, the
  // vnode should not be considered dynamic due to handler caching.
  a.patchFlag !== 32 && ye.push(a), a;
}
const Q = Dl;
function Dl(e, t = null, s = null, n = 0, r = null, i = !1) {
  if ((!e || e === nl) && (e = et), Zt(e)) {
    const o = dt(
      e,
      t,
      !0
      /* mergeRef: true */
    );
    return s && ln(o, s), Ot > 0 && !i && ye && (o.shapeFlag & 6 ? ye[ye.indexOf(e)] = o : ye.push(o)), o.patchFlag = -2, o;
  }
  if (ql(e) && (e = e.__vccOpts), t) {
    t = Fl(t);
    let { class: o, style: a } = t;
    o && !z(o) && (t.class = Ee(o)), W(a) && (tn(a) && !R(a) && (a = ue({}, a)), t.style = qs(a));
  }
  const l = z(e) ? 1 : kr(e) ? 128 : Ki(e) ? 64 : W(e) ? 4 : I(e) ? 2 : 0;
  return j(
    e,
    t,
    s,
    n,
    r,
    l,
    i,
    !0
  );
}
function Fl(e) {
  return e ? tn(e) || Ar(e) ? ue({}, e) : e : null;
}
function dt(e, t, s = !1, n = !1) {
  const { props: r, ref: i, patchFlag: l, children: o, transition: a } = e, d = t ? Ul(r || {}, t) : r, c = {
    __v_isVNode: !0,
    __v_skip: !0,
    type: e.type,
    props: d,
    key: d && Hr(d),
    ref: t && t.ref ? (
      // #2078 in the case of <component :is="vnode" ref="extra"/>
      // if the vnode itself already has a ref, cloneVNode will need to merge
      // the refs so the single vnode can be set on multiple refs
      s && i ? R(i) ? i.concat(Wt(t)) : [i, Wt(t)] : Wt(t)
    ) : i,
    scopeId: e.scopeId,
    slotScopeIds: e.slotScopeIds,
    children: o,
    target: e.target,
    targetStart: e.targetStart,
    targetAnchor: e.targetAnchor,
    staticCount: e.staticCount,
    shapeFlag: e.shapeFlag,
    // if the vnode is cloned with extra props, we can no longer assume its
    // existing patch flag to be reliable and need to add the FULL_PROPS flag.
    // note: preserve flag for fragments since they use the flag for children
    // fast paths only.
    patchFlag: t && e.type !== Z ? l === -1 ? 16 : l | 16 : l,
    dynamicProps: e.dynamicProps,
    dynamicChildren: e.dynamicChildren,
    appContext: e.appContext,
    dirs: e.dirs,
    transition: a,
    // These should technically only be non-null on mounted VNodes. However,
    // they *should* be copied for kept-alive vnodes. So we just always copy
    // them since them being non-null during a mount doesn't affect the logic as
    // they will simply be overwritten.
    component: e.component,
    suspense: e.suspense,
    ssContent: e.ssContent && dt(e.ssContent),
    ssFallback: e.ssFallback && dt(e.ssFallback),
    el: e.el,
    anchor: e.anchor,
    ctx: e.ctx,
    ce: e.ce
  };
  return a && n && nn(
    c,
    a.clone(c)
  ), c;
}
function ce(e = " ", t = 0) {
  return Q(cs, null, e, t);
}
function ws(e, t) {
  const s = Q(Bt, null, e);
  return s.staticCount = t, s;
}
function q(e = "", t = !1) {
  return t ? (E(), Nl(et, null, e)) : Q(et, null, e);
}
function Ve(e) {
  return e == null || typeof e == "boolean" ? Q(et) : R(e) ? Q(
    Z,
    null,
    // #3666, avoid reference pollution when reusing vnode
    e.slice()
  ) : Zt(e) ? He(e) : Q(cs, null, String(e));
}
function He(e) {
  return e.el === null && e.patchFlag !== -1 || e.memo ? e : dt(e);
}
function ln(e, t) {
  let s = 0;
  const { shapeFlag: n } = e;
  if (t == null)
    t = null;
  else if (R(t))
    s = 16;
  else if (typeof t == "object")
    if (n & 65) {
      const r = t.default;
      r && (r._c && (r._d = !1), ln(e, r()), r._c && (r._d = !0));
      return;
    } else {
      s = 32;
      const r = t._;
      !r && !Ar(t) ? t._ctx = me : r === 3 && me && (me.slots._ === 1 ? t._ = 1 : (t._ = 2, e.patchFlag |= 1024));
    }
  else I(t) ? (t = { default: t, _ctx: me }, s = 32) : (t = String(t), n & 64 ? (s = 16, t = [ce(t)]) : s = 8);
  e.children = t, e.shapeFlag |= s;
}
function Ul(...e) {
  const t = {};
  for (let s = 0; s < e.length; s++) {
    const n = e[s];
    for (const r in n)
      if (r === "class")
        t.class !== n.class && (t.class = Ee([t.class, n.class]));
      else if (r === "style")
        t.style = qs([t.style, n.style]);
      else if (ss(r)) {
        const i = t[r], l = n[r];
        l && i !== l && !(R(i) && i.includes(l)) && (t[r] = i ? [].concat(i, l) : l);
      } else r !== "" && (t[r] = n[r]);
  }
  return t;
}
function Se(e, t, s, n = null) {
  Re(e, t, 7, [
    s,
    n
  ]);
}
const kl = Tr();
let Jl = 0;
function Hl(e, t, s) {
  const n = e.type, r = (t ? t.appContext : e.appContext) || kl, i = {
    uid: Jl++,
    vnode: e,
    type: n,
    parent: t,
    appContext: r,
    root: null,
    // to be immediately set
    next: null,
    subTree: null,
    // will be set synchronously right after creation
    effect: null,
    update: null,
    // will be set synchronously right after creation
    job: null,
    scope: new li(
      !0
      /* detached */
    ),
    render: null,
    proxy: null,
    exposed: null,
    exposeProxy: null,
    withProxy: null,
    provides: t ? t.provides : Object.create(r.provides),
    ids: t ? t.ids : ["", 0, 0],
    accessCache: null,
    renderCache: [],
    // local resolved assets
    components: null,
    directives: null,
    // resolved props and emits options
    propsOptions: Vr(n, r),
    emitsOptions: Ur(n, r),
    // emit
    emit: null,
    // to be set immediately
    emitted: null,
    // props default value
    propsDefaults: H,
    // inheritAttrs
    inheritAttrs: n.inheritAttrs,
    // state
    ctx: H,
    data: H,
    props: H,
    attrs: H,
    slots: H,
    refs: H,
    setupState: H,
    setupContext: null,
    // suspense related
    suspense: s,
    suspenseId: s ? s.pendingId : 0,
    asyncDep: null,
    asyncResolved: !1,
    // lifecycle hooks
    // not using enums here because it results in computed properties
    isMounted: !1,
    isUnmounted: !1,
    isDeactivated: !1,
    bc: null,
    c: null,
    bm: null,
    m: null,
    bu: null,
    u: null,
    um: null,
    bum: null,
    da: null,
    a: null,
    rtg: null,
    rtc: null,
    ec: null,
    sp: null
  };
  return i.ctx = { _: i }, i.root = t ? t.root : i, i.emit = El.bind(null, i), e.ce && e.ce(i), i;
}
let ne = null, Qt, ks;
{
  const e = ls(), t = (s, n) => {
    let r;
    return (r = e[s]) || (r = e[s] = []), r.push(n), (i) => {
      r.length > 1 ? r.forEach((l) => l(i)) : r[0](i);
    };
  };
  Qt = t(
    "__VUE_INSTANCE_SETTERS__",
    (s) => ne = s
  ), ks = t(
    "__VUE_SSR_SETTERS__",
    (s) => Pt = s
  );
}
const It = (e) => {
  const t = ne;
  return Qt(e), e.scope.on(), () => {
    e.scope.off(), Qt(t);
  };
}, An = () => {
  ne && ne.scope.off(), Qt(null);
};
function Lr(e) {
  return e.vnode.shapeFlag & 4;
}
let Pt = !1;
function Ll(e, t = !1, s = !1) {
  t && ks(t);
  const { props: n, children: r } = e.vnode, i = Lr(e);
  pl(e, n, i, t), bl(e, r, s);
  const l = i ? Kl(e, t) : void 0;
  return t && ks(!1), l;
}
function Kl(e, t) {
  const s = e.type;
  e.accessCache = /* @__PURE__ */ Object.create(null), e.proxy = new Proxy(e.ctx, il);
  const { setup: n } = s;
  if (n) {
    Be();
    const r = e.setupContext = n.length > 1 ? Wl(e) : null, i = It(e), l = Rt(
      n,
      e,
      0,
      [
        e.props,
        r
      ]
    ), o = Kn(l);
    if (We(), i(), (o || e.sp) && !Tt(e) && br(e), o) {
      if (l.then(An, An), t)
        return l.then((a) => {
          En(e, a);
        }).catch((a) => {
          as(a, e, 0);
        });
      e.asyncDep = l;
    } else
      En(e, l);
  } else
    Kr(e);
}
function En(e, t, s) {
  I(t) ? e.type.__ssrInlineRender ? e.ssrRender = t : e.render = t : W(t) && (e.setupState = dr(t)), Kr(e);
}
function Kr(e, t, s) {
  const n = e.type;
  e.render || (e.render = n.render || Pe);
  {
    const r = It(e);
    Be();
    try {
      ll(e);
    } finally {
      We(), r();
    }
  }
}
const Bl = {
  get(e, t) {
    return te(e, "get", ""), e[t];
  }
};
function Wl(e) {
  const t = (s) => {
    e.exposed = s || {};
  };
  return {
    attrs: new Proxy(e.attrs, Bl),
    slots: e.slots,
    emit: e.emit,
    expose: t
  };
}
function ds(e) {
  return e.exposed ? e.exposeProxy || (e.exposeProxy = new Proxy(dr(Ai(e.exposed)), {
    get(t, s) {
      if (s in t)
        return t[s];
      if (s in Ct)
        return Ct[s](e);
    },
    has(t, s) {
      return s in t || s in Ct;
    }
  })) : e.proxy;
}
function $l(e, t = !0) {
  return I(e) ? e.displayName || e.name : e.name || t && e.__name;
}
function ql(e) {
  return I(e) && "__vccOpts" in e;
}
const Gl = (e, t) => Mi(e, t, Pt);
function Yl(e, t, s) {
  const n = arguments.length;
  return n === 2 ? W(t) && !R(t) ? Zt(t) ? Q(e, null, [t]) : Q(e, t) : Q(e, null, t) : (n > 3 ? s = Array.prototype.slice.call(arguments, 2) : n === 3 && Zt(s) && (s = [s]), Q(e, t, s));
}
const zl = "3.5.13";
/**
* @vue/runtime-dom v3.5.13
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let Js;
const Vn = typeof window < "u" && window.trustedTypes;
if (Vn)
  try {
    Js = /* @__PURE__ */ Vn.createPolicy("vue", {
      createHTML: (e) => e
    });
  } catch {
  }
const Br = Js ? (e) => Js.createHTML(e) : (e) => e, Xl = "http://www.w3.org/2000/svg", Zl = "http://www.w3.org/1998/Math/MathML", Me = typeof document < "u" ? document : null, On = Me && /* @__PURE__ */ Me.createElement("template"), Ql = {
  insert: (e, t, s) => {
    t.insertBefore(e, s || null);
  },
  remove: (e) => {
    const t = e.parentNode;
    t && t.removeChild(e);
  },
  createElement: (e, t, s, n) => {
    const r = t === "svg" ? Me.createElementNS(Xl, e) : t === "mathml" ? Me.createElementNS(Zl, e) : s ? Me.createElement(e, { is: s }) : Me.createElement(e);
    return e === "select" && n && n.multiple != null && r.setAttribute("multiple", n.multiple), r;
  },
  createText: (e) => Me.createTextNode(e),
  createComment: (e) => Me.createComment(e),
  setText: (e, t) => {
    e.nodeValue = t;
  },
  setElementText: (e, t) => {
    e.textContent = t;
  },
  parentNode: (e) => e.parentNode,
  nextSibling: (e) => e.nextSibling,
  querySelector: (e) => Me.querySelector(e),
  setScopeId(e, t) {
    e.setAttribute(t, "");
  },
  // __UNSAFE__
  // Reason: innerHTML.
  // Static content here can only come from compiled templates.
  // As long as the user only uses trusted templates, this is safe.
  insertStaticContent(e, t, s, n, r, i) {
    const l = s ? s.previousSibling : t.lastChild;
    if (r && (r === i || r.nextSibling))
      for (; t.insertBefore(r.cloneNode(!0), s), !(r === i || !(r = r.nextSibling)); )
        ;
    else {
      On.innerHTML = Br(
        n === "svg" ? `<svg>${e}</svg>` : n === "mathml" ? `<math>${e}</math>` : e
      );
      const o = On.content;
      if (n === "svg" || n === "mathml") {
        const a = o.firstChild;
        for (; a.firstChild; )
          o.appendChild(a.firstChild);
        o.removeChild(a);
      }
      t.insertBefore(o, s);
    }
    return [
      // first
      l ? l.nextSibling : t.firstChild,
      // last
      s ? s.previousSibling : t.lastChild
    ];
  }
}, eo = Symbol("_vtc");
function to(e, t, s) {
  const n = e[eo];
  n && (t = (t ? [t, ...n] : [...n]).join(" ")), t == null ? e.removeAttribute("class") : s ? e.setAttribute("class", t) : e.className = t;
}
const es = Symbol("_vod"), Wr = Symbol("_vsh"), rt = {
  beforeMount(e, { value: t }, { transition: s }) {
    e[es] = e.style.display === "none" ? "" : e.style.display, s && t ? s.beforeEnter(e) : jt(e, t);
  },
  mounted(e, { value: t }, { transition: s }) {
    s && t && s.enter(e);
  },
  updated(e, { value: t, oldValue: s }, { transition: n }) {
    !t != !s && (n ? t ? (n.beforeEnter(e), jt(e, !0), n.enter(e)) : n.leave(e, () => {
      jt(e, !1);
    }) : jt(e, t));
  },
  beforeUnmount(e, { value: t }) {
    jt(e, t);
  }
};
function jt(e, t) {
  e.style.display = t ? e[es] : "none", e[Wr] = !t;
}
const so = Symbol(""), no = /(^|;)\s*display\s*:/;
function ro(e, t, s) {
  const n = e.style, r = z(s);
  let i = !1;
  if (s && !r) {
    if (t)
      if (z(t))
        for (const l of t.split(";")) {
          const o = l.slice(0, l.indexOf(":")).trim();
          s[o] == null && $t(n, o, "");
        }
      else
        for (const l in t)
          s[l] == null && $t(n, l, "");
    for (const l in s)
      l === "display" && (i = !0), $t(n, l, s[l]);
  } else if (r) {
    if (t !== s) {
      const l = n[so];
      l && (s += ";" + l), n.cssText = s, i = no.test(s);
    }
  } else t && e.removeAttribute("style");
  es in e && (e[es] = i ? n.display : "", e[Wr] && (n.display = "none"));
}
const Pn = /\s*!important$/;
function $t(e, t, s) {
  if (R(s))
    s.forEach((n) => $t(e, t, n));
  else if (s == null && (s = ""), t.startsWith("--"))
    e.setProperty(t, s);
  else {
    const n = io(e, t);
    Pn.test(s) ? e.setProperty(
      tt(n),
      s.replace(Pn, ""),
      "important"
    ) : e[n] = s;
  }
}
const Rn = ["Webkit", "Moz", "ms"], Ts = {};
function io(e, t) {
  const s = Ts[t];
  if (s)
    return s;
  let n = je(t);
  if (n !== "filter" && n in e)
    return Ts[t] = n;
  n = is(n);
  for (let r = 0; r < Rn.length; r++) {
    const i = Rn[r] + n;
    if (i in e)
      return Ts[t] = i;
  }
  return t;
}
const In = "http://www.w3.org/1999/xlink";
function Mn(e, t, s, n, r, i = ii(t)) {
  n && t.startsWith("xlink:") ? s == null ? e.removeAttributeNS(In, t.slice(6, t.length)) : e.setAttributeNS(In, t, s) : s == null || i && !qn(s) ? e.removeAttribute(t) : e.setAttribute(
    t,
    i ? "" : Ke(s) ? String(s) : s
  );
}
function Nn(e, t, s, n, r) {
  if (t === "innerHTML" || t === "textContent") {
    s != null && (e[t] = t === "innerHTML" ? Br(s) : s);
    return;
  }
  const i = e.tagName;
  if (t === "value" && i !== "PROGRESS" && // custom elements may use _value internally
  !i.includes("-")) {
    const o = i === "OPTION" ? e.getAttribute("value") || "" : e.value, a = s == null ? (
      // #11647: value should be set as empty string for null and undefined,
      // but <input type="checkbox"> should be set as 'on'.
      e.type === "checkbox" ? "on" : ""
    ) : String(s);
    (o !== a || !("_value" in e)) && (e.value = a), s == null && e.removeAttribute(t), e._value = s;
    return;
  }
  let l = !1;
  if (s === "" || s == null) {
    const o = typeof e[t];
    o === "boolean" ? s = qn(s) : s == null && o === "string" ? (s = "", l = !0) : o === "number" && (s = 0, l = !0);
  }
  try {
    e[t] = s;
  } catch {
  }
  l && e.removeAttribute(r || t);
}
function it(e, t, s, n) {
  e.addEventListener(t, s, n);
}
function lo(e, t, s, n) {
  e.removeEventListener(t, s, n);
}
const Dn = Symbol("_vei");
function oo(e, t, s, n, r = null) {
  const i = e[Dn] || (e[Dn] = {}), l = i[t];
  if (n && l)
    l.value = n;
  else {
    const [o, a] = ao(t);
    if (n) {
      const d = i[t] = co(
        n,
        r
      );
      it(e, o, d, a);
    } else l && (lo(e, o, l, a), i[t] = void 0);
  }
}
const Fn = /(?:Once|Passive|Capture)$/;
function ao(e) {
  let t;
  if (Fn.test(e)) {
    t = {};
    let n;
    for (; n = e.match(Fn); )
      e = e.slice(0, e.length - n[0].length), t[n[0].toLowerCase()] = !0;
  }
  return [e[2] === ":" ? e.slice(3) : tt(e.slice(2)), t];
}
let Cs = 0;
const uo = /* @__PURE__ */ Promise.resolve(), fo = () => Cs || (uo.then(() => Cs = 0), Cs = Date.now());
function co(e, t) {
  const s = (n) => {
    if (!n._vts)
      n._vts = Date.now();
    else if (n._vts <= s.attached)
      return;
    Re(
      ho(n, s.value),
      t,
      5,
      [n]
    );
  };
  return s.value = e, s.attached = fo(), s;
}
function ho(e, t) {
  if (R(t)) {
    const s = e.stopImmediatePropagation;
    return e.stopImmediatePropagation = () => {
      s.call(e), e._stopped = !0;
    }, t.map(
      (n) => (r) => !r._stopped && n && n(r)
    );
  } else
    return t;
}
const Un = (e) => e.charCodeAt(0) === 111 && e.charCodeAt(1) === 110 && // lowercase letter
e.charCodeAt(2) > 96 && e.charCodeAt(2) < 123, po = (e, t, s, n, r, i) => {
  const l = r === "svg";
  t === "class" ? to(e, n, l) : t === "style" ? ro(e, s, n) : ss(t) ? Bs(t) || oo(e, t, s, n, i) : (t[0] === "." ? (t = t.slice(1), !0) : t[0] === "^" ? (t = t.slice(1), !1) : go(e, t, n, l)) ? (Nn(e, t, n), !e.tagName.includes("-") && (t === "value" || t === "checked" || t === "selected") && Mn(e, t, n, l, i, t !== "value")) : /* #11081 force set props for possible async custom element */ e._isVueCE && (/[A-Z]/.test(t) || !z(n)) ? Nn(e, je(t), n, i, t) : (t === "true-value" ? e._trueValue = n : t === "false-value" && (e._falseValue = n), Mn(e, t, n, l));
};
function go(e, t, s, n) {
  if (n)
    return !!(t === "innerHTML" || t === "textContent" || t in e && Un(t) && I(s));
  if (t === "spellcheck" || t === "draggable" || t === "translate" || t === "form" || t === "list" && e.tagName === "INPUT" || t === "type" && e.tagName === "TEXTAREA")
    return !1;
  if (t === "width" || t === "height") {
    const r = e.tagName;
    if (r === "IMG" || r === "VIDEO" || r === "CANVAS" || r === "SOURCE")
      return !1;
  }
  return Un(t) && z(s) ? !1 : t in e;
}
const kn = (e) => {
  const t = e.props["onUpdate:modelValue"] || !1;
  return R(t) ? (s) => Lt(t, s) : t;
};
function mo(e) {
  e.target.composing = !0;
}
function Jn(e) {
  const t = e.target;
  t.composing && (t.composing = !1, t.dispatchEvent(new Event("input")));
}
const Ss = Symbol("_assign"), ts = {
  created(e, { modifiers: { lazy: t, trim: s, number: n } }, r) {
    e[Ss] = kn(r);
    const i = n || r.props && r.props.type === "number";
    it(e, t ? "change" : "input", (l) => {
      if (l.target.composing) return;
      let o = e.value;
      s && (o = o.trim()), i && (o = As(o)), e[Ss](o);
    }), s && it(e, "change", () => {
      e.value = e.value.trim();
    }), t || (it(e, "compositionstart", mo), it(e, "compositionend", Jn), it(e, "change", Jn));
  },
  // set value on mounted so it's after min/max for type="range"
  mounted(e, { value: t }) {
    e.value = t ?? "";
  },
  beforeUpdate(e, { value: t, oldValue: s, modifiers: { lazy: n, trim: r, number: i } }, l) {
    if (e[Ss] = kn(l), e.composing) return;
    const o = (i || e.type === "number") && !/^0\d/.test(e.value) ? As(e.value) : e.value, a = t ?? "";
    o !== a && (document.activeElement === e && e.type !== "range" && (n && t === s || r && e.value.trim() === a) || (e.value = a));
  }
}, yo = /* @__PURE__ */ ue({ patchProp: po }, Ql);
let Hn;
function bo() {
  return Hn || (Hn = xl(yo));
}
const jo = (...e) => {
  const t = bo().createApp(...e), { mount: s } = t;
  return t.mount = (n) => {
    const r = vo(n);
    if (!r) return;
    const i = t._component;
    !I(i) && !i.render && !i.template && (i.template = r.innerHTML), r.nodeType === 1 && (r.textContent = "");
    const l = s(r, !1, xo(r));
    return r instanceof Element && (r.removeAttribute("v-cloak"), r.setAttribute("data-v-app", "")), l;
  }, t;
};
function xo(e) {
  if (e instanceof SVGElement)
    return "svg";
  if (typeof MathMLElement == "function" && e instanceof MathMLElement)
    return "mathml";
}
function vo(e) {
  return z(e) ? document.querySelector(e) : e;
}
const on = (e, t) => {
  const s = e.__vccOpts || e;
  for (const [n, r] of t)
    s[n] = r;
  return s;
}, _o = {
  name: "JsonEditorNode",
  props: {
    modelValue: {
      required: !0
    },
    propKey: {
      type: String,
      default: null
    },
    depth: {
      type: Number,
      default: 0
    },
    arrayIndex: {
      type: Number,
      default: null
    },
    filterText: {
      type: String,
      default: ""
    },
    filteredPaths: {
      type: Array,
      default: () => []
    },
    currentPath: {
      type: String,
      default: ""
    },
    globalCollapsed: {
      type: Boolean,
      default: null
    },
    collapseVersion: {
      type: Number,
      default: 0
    }
  },
  emits: ["update:modelValue", "delete", "update:key", "duplicate"],
  data() {
    return {
      collapsed: !1
    };
  },
  watch: {
    collapseVersion() {
      this.globalCollapsed !== null && (this.collapsed = this.globalCollapsed);
    }
  },
  computed: {
    isObject() {
      return this.modelValue !== null && typeof this.modelValue == "object" && !Array.isArray(this.modelValue);
    },
    isArray() {
      return Array.isArray(this.modelValue);
    },
    objectKeys() {
      return this.isObject ? Object.keys(this.modelValue) : [];
    },
    valueType() {
      return this.modelValue === null ? "null" : Array.isArray(this.modelValue) ? "array" : typeof this.modelValue;
    },
    shouldShow() {
      return !this.filterText || this.depth === 0 ? !0 : this.isPathMatched(this.currentPath);
    },
    isHighlighted() {
      return this.filterText ? this.filteredPaths.includes(this.currentPath) : !1;
    }
  },
  methods: {
    toggleCollapse() {
      this.collapsed = !this.collapsed;
    },
    updateObjectValue(e, t) {
      const s = { ...this.modelValue };
      s[e] = t, this.$emit("update:modelValue", s);
    },
    updateArrayValue(e, t) {
      const s = [...this.modelValue];
      s[e] = t, this.$emit("update:modelValue", s);
    },
    deleteProperty(e) {
      const t = { ...this.modelValue };
      delete t[e], this.$emit("update:modelValue", t);
    },
    deleteArrayItem(e) {
      const t = [...this.modelValue];
      t.splice(e, 1), this.$emit("update:modelValue", t);
    },
    addProperty() {
      const e = { ...this.modelValue };
      let t = "newKey", s = 1;
      for (; t in e; )
        t = `newKey${s++}`;
      e[t] = "", this.$emit("update:modelValue", e);
    },
    addArrayItem() {
      const e = [...this.modelValue];
      e.push(""), this.$emit("update:modelValue", e);
    },
    deepClone(e) {
      if (e === null || typeof e != "object") return e;
      if (Array.isArray(e)) return e.map((s) => this.deepClone(s));
      const t = {};
      for (const s in e)
        Object.prototype.hasOwnProperty.call(e, s) && (t[s] = this.deepClone(e[s]));
      return t;
    },
    duplicateProperty(e) {
      const t = { ...this.modelValue };
      let s = `${e}_copy`, n = 1;
      for (; s in t; )
        s = `${e}_copy${n++}`;
      t[s] = this.deepClone(this.modelValue[e]), this.$emit("update:modelValue", t);
    },
    duplicateArrayItem(e) {
      const t = [...this.modelValue], s = this.deepClone(this.modelValue[e]);
      t.splice(e + 1, 0, s), this.$emit("update:modelValue", t);
    },
    updateKey(e, t) {
      if (e === t || t in this.modelValue) return;
      const s = {};
      for (const n of Object.keys(this.modelValue))
        n === e ? s[t] = this.modelValue[e] : s[n] = this.modelValue[n];
      this.$emit("update:modelValue", s);
    },
    changeType(e) {
      let t;
      switch (e) {
        case "string":
          t = "";
          break;
        case "number":
          t = 0;
          break;
        case "boolean":
          t = !1;
          break;
        case "null":
          t = null;
          break;
        case "object":
          t = {};
          break;
        case "array":
          t = [];
          break;
        default:
          t = "";
      }
      this.$emit("update:modelValue", t);
    },
    isPathMatched(e) {
      if (!e) return !0;
      for (const t of this.filteredPaths)
        if (t === e || t.startsWith(e + ".") || e.startsWith(t + ".") || e === t)
          return !0;
      return !1;
    }
  }
}, wo = ["value"], To = {
  key: 1,
  class: "je-text-gray-500"
}, Co = {
  key: 2,
  class: "je-text-gray-400 je-text-sm"
}, So = ["value"], Ao = { class: "je-children je-border-l-2 je-border-gray-200 je-ml-2" }, Eo = { class: "je-text-gray-500 je-ml-6" }, Vo = ["value"], Oo = {
  key: 1,
  class: "je-text-gray-500"
}, Po = {
  key: 2,
  class: "je-text-gray-400 je-text-sm"
}, Ro = ["value"], Io = { class: "je-children je-border-l-2 je-border-blue-200 je-ml-2" }, Mo = { class: "je-text-gray-400 je-text-xs je-mt-2 je-mr-1 je-min-w-20" }, No = { class: "je-text-gray-500 je-ml-6" }, Do = ["value"], Fo = ["value"], Uo = ["value"], ko = ["value"], Jo = ["value"], Ho = {
  key: 4,
  class: "je-text-gray-400 je-italic"
};
function Lo(e, t, s, n, r, i) {
  const l = Is("JsonEditorNode", !0);
  return Oe((E(), V("div", {
    class: Ee(["je-node", { "je-ml-4": s.depth > 0 }])
  }, [
    i.isObject ? (E(), V(Z, { key: 0 }, [
      j("div", {
        class: Ee(["je-flex je-items-center je-gap-2 je-py-1", { "je-highlight": i.isHighlighted }])
      }, [
        j("button", {
          onClick: t[0] || (t[0] = (...o) => i.toggleCollapse && i.toggleCollapse(...o)),
          class: "je-collapse-btn je-w-5 je-h-5 je-flex je-items-center je-justify-center je-text-gray-500"
        }, [
          j("i", {
            class: Ee([r.collapsed ? "fa-solid fa-chevron-right" : "fa-solid fa-chevron-down", "je-text-xs"])
          }, null, 2)
        ]),
        s.propKey !== null ? (E(), V("input", {
          key: 0,
          type: "text",
          value: s.propKey,
          onChange: t[1] || (t[1] = (o) => e.$emit("update:key", o.target.value)),
          class: "je-key-input je-px-2 je-py-0.5 je-border je-border-gray-300 je-rounded je-text-purple-600 je-font-medium je-text-sm je-w-32"
        }, null, 40, wo)) : q("", !0),
        s.propKey !== null ? (E(), V("span", To, ":")) : q("", !0),
        t[23] || (t[23] = j("span", { class: "je-text-gray-500" }, "{", -1)),
        r.collapsed ? (E(), V("span", Co, oe(i.objectKeys.length) + " properties", 1)) : q("", !0),
        j("button", {
          onClick: t[2] || (t[2] = (...o) => i.addProperty && i.addProperty(...o)),
          class: "je-btn je-btn-green je-ml-2 je-px-2 je-py-0.5 je-text-xs je-rounded",
          title: "添加属性"
        }, t[19] || (t[19] = [
          j("i", { class: "fa-solid fa-plus" }, null, -1)
        ])),
        s.arrayIndex !== null ? (E(), V("select", {
          key: 3,
          value: i.valueType,
          onChange: t[3] || (t[3] = (o) => i.changeType(o.target.value)),
          class: "je-select je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-xs je-bg-gray-50"
        }, t[20] || (t[20] = [
          ws('<option value="string">String</option><option value="number">Number</option><option value="boolean">Boolean</option><option value="null">Null</option><option value="object">Object</option><option value="array">Array</option>', 6)
        ]), 40, So)) : q("", !0),
        s.propKey !== null && s.depth > 0 || s.arrayIndex !== null ? (E(), V("button", {
          key: 4,
          onClick: t[4] || (t[4] = (o) => e.$emit("duplicate")),
          class: "je-btn je-btn-blue je-px-2 je-py-0.5 je-text-xs je-rounded",
          title: "复制"
        }, t[21] || (t[21] = [
          j("i", { class: "fa-solid fa-copy" }, null, -1)
        ]))) : q("", !0),
        s.propKey !== null && s.depth > 0 || s.arrayIndex !== null ? (E(), V("button", {
          key: 5,
          onClick: t[5] || (t[5] = (o) => e.$emit("delete")),
          class: "je-btn je-btn-red je-px-2 je-py-0.5 je-text-xs je-rounded",
          title: "删除"
        }, t[22] || (t[22] = [
          j("i", { class: "fa-solid fa-trash" }, null, -1)
        ]))) : q("", !0)
      ], 2),
      Oe(j("div", Ao, [
        (E(!0), V(Z, null, Ms(i.objectKeys, (o, a) => (E(), V("div", {
          key: o,
          class: "je-relative"
        }, [
          Q(l, {
            modelValue: s.modelValue[o],
            propKey: o,
            depth: s.depth + 1,
            filterText: s.filterText,
            filteredPaths: s.filteredPaths,
            currentPath: s.currentPath ? `${s.currentPath}.${o}` : o,
            globalCollapsed: s.globalCollapsed,
            collapseVersion: s.collapseVersion,
            "onUpdate:modelValue": (d) => i.updateObjectValue(o, d),
            onDelete: (d) => i.deleteProperty(o),
            "onUpdate:key": (d) => i.updateKey(o, d),
            onDuplicate: (d) => i.duplicateProperty(o)
          }, null, 8, ["modelValue", "propKey", "depth", "filterText", "filteredPaths", "currentPath", "globalCollapsed", "collapseVersion", "onUpdate:modelValue", "onDelete", "onUpdate:key", "onDuplicate"])
        ]))), 128))
      ], 512), [
        [rt, !r.collapsed]
      ]),
      Oe(j("div", Eo, "}", 512), [
        [rt, !r.collapsed]
      ])
    ], 64)) : i.isArray ? (E(), V(Z, { key: 1 }, [
      j("div", {
        class: Ee(["je-flex je-items-center je-gap-2 je-py-1", { "je-highlight": i.isHighlighted }])
      }, [
        j("button", {
          onClick: t[6] || (t[6] = (...o) => i.toggleCollapse && i.toggleCollapse(...o)),
          class: "je-collapse-btn je-w-5 je-h-5 je-flex je-items-center je-justify-center je-text-gray-500"
        }, [
          j("i", {
            class: Ee([r.collapsed ? "fa-solid fa-chevron-right" : "fa-solid fa-chevron-down", "je-text-xs"])
          }, null, 2)
        ]),
        s.propKey !== null ? (E(), V("input", {
          key: 0,
          type: "text",
          value: s.propKey,
          onChange: t[7] || (t[7] = (o) => e.$emit("update:key", o.target.value)),
          class: "je-key-input je-px-2 je-py-0.5 je-border je-border-gray-300 je-rounded je-text-purple-600 je-font-medium je-text-sm je-w-32"
        }, null, 40, Vo)) : q("", !0),
        s.propKey !== null ? (E(), V("span", Oo, ":")) : q("", !0),
        t[28] || (t[28] = j("span", { class: "je-text-gray-500" }, "[", -1)),
        r.collapsed ? (E(), V("span", Po, oe(s.modelValue.length) + " items", 1)) : q("", !0),
        j("button", {
          onClick: t[8] || (t[8] = (...o) => i.addArrayItem && i.addArrayItem(...o)),
          class: "je-btn je-btn-green je-ml-2 je-px-2 je-py-0.5 je-text-xs je-rounded",
          title: "添加元素"
        }, t[24] || (t[24] = [
          j("i", { class: "fa-solid fa-plus" }, null, -1)
        ])),
        s.arrayIndex !== null ? (E(), V("select", {
          key: 3,
          value: i.valueType,
          onChange: t[9] || (t[9] = (o) => i.changeType(o.target.value)),
          class: "je-select je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-xs je-bg-gray-50"
        }, t[25] || (t[25] = [
          ws('<option value="string">String</option><option value="number">Number</option><option value="boolean">Boolean</option><option value="null">Null</option><option value="object">Object</option><option value="array">Array</option>', 6)
        ]), 40, Ro)) : q("", !0),
        s.propKey !== null && s.depth > 0 || s.arrayIndex !== null ? (E(), V("button", {
          key: 4,
          onClick: t[10] || (t[10] = (o) => e.$emit("duplicate")),
          class: "je-btn je-btn-blue je-px-2 je-py-0.5 je-text-xs je-rounded",
          title: "复制"
        }, t[26] || (t[26] = [
          j("i", { class: "fa-solid fa-copy" }, null, -1)
        ]))) : q("", !0),
        s.propKey !== null && s.depth > 0 || s.arrayIndex !== null ? (E(), V("button", {
          key: 5,
          onClick: t[11] || (t[11] = (o) => e.$emit("delete")),
          class: "je-btn je-btn-red je-px-2 je-py-0.5 je-text-xs je-rounded",
          title: "删除"
        }, t[27] || (t[27] = [
          j("i", { class: "fa-solid fa-trash" }, null, -1)
        ]))) : q("", !0)
      ], 2),
      Oe(j("div", Io, [
        (E(!0), V(Z, null, Ms(s.modelValue, (o, a) => (E(), V("div", {
          key: a,
          class: "je-relative je-flex je-items-start"
        }, [
          j("span", Mo, oe(a), 1),
          Q(l, {
            modelValue: o,
            propKey: null,
            depth: s.depth + 1,
            arrayIndex: a,
            filterText: s.filterText,
            filteredPaths: s.filteredPaths,
            currentPath: s.currentPath ? `${s.currentPath}.${a}` : `${a}`,
            globalCollapsed: s.globalCollapsed,
            collapseVersion: s.collapseVersion,
            "onUpdate:modelValue": (d) => i.updateArrayValue(a, d),
            onDelete: (d) => i.deleteArrayItem(a),
            onDuplicate: (d) => i.duplicateArrayItem(a)
          }, null, 8, ["modelValue", "depth", "arrayIndex", "filterText", "filteredPaths", "currentPath", "globalCollapsed", "collapseVersion", "onUpdate:modelValue", "onDelete", "onDuplicate"])
        ]))), 128))
      ], 512), [
        [rt, !r.collapsed]
      ]),
      Oe(j("div", No, "]", 512), [
        [rt, !r.collapsed]
      ])
    ], 64)) : (E(), V("div", {
      key: 2,
      class: Ee(["je-flex je-items-center je-gap-2 je-py-1 je-pl-6", { "je-highlight": i.isHighlighted }])
    }, [
      s.propKey !== null ? (E(), V(Z, { key: 0 }, [
        j("input", {
          type: "text",
          value: s.propKey,
          onChange: t[12] || (t[12] = (o) => e.$emit("update:key", o.target.value)),
          class: "je-key-input je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-purple-600 je-font-medium je-text-sm je-w-32"
        }, null, 40, Do),
        t[29] || (t[29] = j("span", { class: "je-text-gray-500" }, ":", -1))
      ], 64)) : q("", !0),
      j("select", {
        value: i.valueType,
        onChange: t[13] || (t[13] = (o) => i.changeType(o.target.value)),
        class: "je-select je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-xs je-bg-gray-50"
      }, t[30] || (t[30] = [
        ws('<option value="string">String</option><option value="number">Number</option><option value="boolean">Boolean</option><option value="null">Null</option><option value="object">Object</option><option value="array">Array</option>', 6)
      ]), 40, Fo),
      i.valueType === "string" ? (E(), V("input", {
        key: 1,
        type: "text",
        value: s.modelValue,
        onInput: t[14] || (t[14] = (o) => e.$emit("update:modelValue", o.target.value)),
        class: "je-value-input je-flex-1 je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-green-600 je-text-sm",
        placeholder: "输入字符串值"
      }, null, 40, Uo)) : i.valueType === "number" ? (E(), V("input", {
        key: 2,
        type: "number",
        value: s.modelValue,
        onInput: t[15] || (t[15] = (o) => e.$emit("update:modelValue", parseFloat(o.target.value) || 0)),
        class: "je-value-input je-flex-1 je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-blue-600 je-text-sm",
        placeholder: "输入数字"
      }, null, 40, ko)) : i.valueType === "boolean" ? (E(), V("select", {
        key: 3,
        value: s.modelValue,
        onChange: t[16] || (t[16] = (o) => e.$emit("update:modelValue", o.target.value === "true")),
        class: "je-select je-px-2 je-py-1 je-border je-border-gray-300 je-rounded je-text-orange-600 je-text-sm"
      }, t[31] || (t[31] = [
        j("option", { value: !0 }, "true", -1),
        j("option", { value: !1 }, "false", -1)
      ]), 40, Jo)) : i.valueType === "null" ? (E(), V("span", Ho, "null")) : q("", !0),
      s.depth > 0 ? (E(), V("button", {
        key: 5,
        onClick: t[17] || (t[17] = (o) => e.$emit("duplicate")),
        class: "je-btn je-btn-blue je-px-2 je-py-0.5 je-text-xs je-rounded",
        title: "复制"
      }, t[32] || (t[32] = [
        j("i", { class: "fa-solid fa-copy" }, null, -1)
      ]))) : q("", !0),
      s.depth > 0 ? (E(), V("button", {
        key: 6,
        onClick: t[18] || (t[18] = (o) => e.$emit("delete")),
        class: "je-btn je-btn-red je-px-2 je-py-0.5 je-text-xs je-rounded",
        title: "删除"
      }, t[33] || (t[33] = [
        j("i", { class: "fa-solid fa-trash" }, null, -1)
      ]))) : q("", !0)
    ], 2))
  ], 2)), [
    [rt, i.shouldShow]
  ]);
}
const Hs = /* @__PURE__ */ on(_o, [["render", Lo]]), Ko = {
  name: "TemplateDialog",
  props: {
    visible: {
      type: Boolean,
      default: !1
    },
    apiBaseUrl: {
      type: String,
      default: "/api/templates"
    }
  },
  emits: ["update:visible", "insert"],
  data() {
    return {
      templates: [],
      loading: !1,
      saving: !1,
      error: null,
      editMode: !1,
      editingTemplate: {
        id: null,
        name: "",
        content: ""
      },
      contentError: null
    };
  },
  watch: {
    visible(e) {
      e && this.$nextTick(() => {
        this.loadTemplates();
      });
    },
    apiBaseUrl() {
      this.visible && this.loadTemplates();
    }
  },
  methods: {
    close() {
      this.$emit("update:visible", !1), this.editMode = !1, this.error = null;
    },
    async loadTemplates() {
      this.loading = !0, this.error = null;
      try {
        const e = await fetch(this.apiBaseUrl);
        if (!e.ok) throw new Error("加载失败");
        const t = await e.json();
        this.templates = t.data;
      } catch (e) {
        this.error = e.message || "加载模板失败";
      } finally {
        this.loading = !1;
      }
    },
    getPreview(e) {
      try {
        const t = typeof e == "string" ? e : JSON.stringify(e);
        return t.length > 50 ? t.substring(0, 50) + "..." : t;
      } catch {
        return "...";
      }
    },
    startCreate() {
      this.editingTemplate = { id: null, name: "", content: `{
  
}` }, this.contentError = null, this.editMode = !0;
    },
    startEdit(e) {
      this.editingTemplate = {
        id: e.id,
        name: e.name,
        content: typeof e.content == "string" ? e.content : JSON.stringify(e.content, null, 2)
      }, this.contentError = null, this.editMode = !0;
    },
    cancelEdit() {
      this.editMode = !1, this.editingTemplate = { id: null, name: "", content: "" }, this.contentError = null;
    },
    validateContent() {
      try {
        return JSON.parse(this.editingTemplate.content), this.contentError = null, !0;
      } catch (e) {
        return this.contentError = "JSON 格式错误: " + e.message, !1;
      }
    },
    async saveTemplate() {
      if (!this.editingTemplate.name.trim()) {
        this.contentError = "请输入模板名称";
        return;
      }
      if (this.validateContent()) {
        this.saving = !0;
        try {
          const e = !!this.editingTemplate.id, t = e ? `${this.apiBaseUrl}/${this.editingTemplate.id}` : this.apiBaseUrl;
          if (!(await fetch(t, {
            method: e ? "PUT" : "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id: this.editingTemplate.id,
              name: this.editingTemplate.name,
              content: this.editingTemplate.content
            })
          })).ok) throw new Error("保存失败");
          await this.loadTemplates(), this.cancelEdit();
        } catch (e) {
          this.contentError = e.message || "保存失败";
        } finally {
          this.saving = !1;
        }
      }
    },
    async deleteTemplate(e) {
      if (confirm(`确定要删除模板 "${e.name}" 吗？`))
        try {
          if (!(await fetch(`${this.apiBaseUrl}/${e.id}`, {
            method: "DELETE",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id: e.id
            })
          })).ok) throw new Error("删除失败");
          await this.loadTemplates();
        } catch (t) {
          this.error = t.message || "删除失败";
        }
    },
    insertTemplate(e) {
      try {
        const t = typeof e.content == "string" ? JSON.parse(e.content) : e.content;
        this.$emit("insert", t), this.close();
      } catch {
        this.error = "模板内容解析失败";
      }
    }
  }
}, Bo = {
  key: 0,
  class: "je-dialog-overlay"
}, Wo = { class: "je-dialog" }, $o = { class: "je-dialog-header" }, qo = { class: "je-dialog-body" }, Go = {
  key: 0,
  class: "je-text-center je-py-4 je-text-gray-500"
}, Yo = {
  key: 1,
  class: "je-text-red-500 je-text-sm je-p-4 je-bg-red-50 je-rounded"
}, zo = {
  key: 2,
  class: "je-template-form"
}, Xo = { class: "je-form-group" }, Zo = { class: "je-form-group je-mt-2" }, Qo = {
  key: 0,
  class: "je-text-red-500 je-text-xs je-mt-1"
}, ea = { class: "je-form-actions je-mt-4 je-flex je-justify-end je-gap-2" }, ta = ["disabled"], sa = {
  key: 0,
  class: "fa-solid fa-spinner fa-spin je-mr-1"
}, na = { key: 3 }, ra = { class: "je-template-toolbar je-flex je-justify-between je-items-center je-mb-3" }, ia = { class: "je-text-sm je-text-gray-500" }, la = {
  key: 0,
  class: "je-text-center je-py-8 je-text-gray-400"
}, oa = {
  key: 1,
  class: "je-template-list"
}, aa = { class: "je-template-info" }, ua = { class: "je-template-name" }, fa = { class: "je-template-preview" }, ca = { class: "je-template-actions" }, da = ["onClick"], ha = ["onClick"], pa = ["onClick"];
function ga(e, t, s, n, r, i) {
  return s.visible ? (E(), V("div", Bo, [
    j("div", Wo, [
      j("div", $o, [
        t[8] || (t[8] = j("span", { class: "je-font-semibold je-text-gray-700" }, "内容模板", -1)),
        j("button", {
          onClick: t[0] || (t[0] = (...l) => i.close && i.close(...l)),
          class: "je-dialog-close"
        }, t[7] || (t[7] = [
          j("i", { class: "fa-solid fa-times" }, null, -1)
        ]))
      ]),
      j("div", qo, [
        r.loading ? (E(), V("div", Go, t[9] || (t[9] = [
          j("i", { class: "fa-solid fa-spinner fa-spin je-mr-2" }, null, -1),
          ce("加载中... ")
        ]))) : r.error ? (E(), V("div", Yo, [
          t[10] || (t[10] = j("i", { class: "fa-solid fa-exclamation-triangle je-mr-2" }, null, -1)),
          ce(oe(r.error) + " ", 1),
          j("button", {
            onClick: t[1] || (t[1] = (...l) => i.loadTemplates && i.loadTemplates(...l)),
            class: "je-ml-2 je-text-blue-600 hover:je-underline"
          }, "重试")
        ])) : r.editMode ? (E(), V("div", zo, [
          j("div", Xo, [
            t[11] || (t[11] = j("label", { class: "je-form-label" }, "模板名称", -1)),
            Oe(j("input", {
              "onUpdate:modelValue": t[2] || (t[2] = (l) => r.editingTemplate.name = l),
              type: "text",
              class: "je-input je-w-full je-px-3 je-py-2 je-border je-border-gray-300 je-rounded",
              placeholder: "输入模板名称"
            }, null, 512), [
              [ts, r.editingTemplate.name]
            ])
          ]),
          j("div", Zo, [
            t[13] || (t[13] = j("label", { class: "je-form-label" }, "模板内容 (JSON)", -1)),
            Oe(j("textarea", {
              "onUpdate:modelValue": t[3] || (t[3] = (l) => r.editingTemplate.content = l),
              class: "je-textarea je-w-full je-h-80 je-p-3 je-font-mono je-text-sm je-border je-border-gray-300 je-rounded je-resize-none",
              placeholder: '{"key": "value"}'
            }, null, 512), [
              [ts, r.editingTemplate.content]
            ]),
            r.contentError ? (E(), V("div", Qo, [
              t[12] || (t[12] = j("i", { class: "fa-solid fa-exclamation-triangle je-mr-1" }, null, -1)),
              ce(oe(r.contentError), 1)
            ])) : q("", !0)
          ]),
          j("div", ea, [
            j("button", {
              onClick: t[4] || (t[4] = (...l) => i.cancelEdit && i.cancelEdit(...l)),
              class: "je-btn je-btn-gray je-px-4 je-py-2 je-text-sm je-rounded"
            }, " 取消 "),
            j("button", {
              onClick: t[5] || (t[5] = (...l) => i.saveTemplate && i.saveTemplate(...l)),
              class: "je-btn je-btn-blue je-px-4 je-py-2 je-text-sm je-rounded",
              disabled: r.saving
            }, [
              r.saving ? (E(), V("i", sa)) : q("", !0),
              ce(" " + oe(r.editingTemplate.id ? "保存" : "新增"), 1)
            ], 8, ta)
          ])
        ])) : (E(), V("div", na, [
          j("div", ra, [
            j("span", ia, "共 " + oe(r.templates.length) + " 个模板", 1),
            j("button", {
              onClick: t[6] || (t[6] = (...l) => i.startCreate && i.startCreate(...l)),
              class: "je-btn je-btn-green je-px-3 je-py-1.5 je-text-xs je-rounded je-mb-3"
            }, t[14] || (t[14] = [
              j("i", { class: "fa-solid fa-plus je-mr-1" }, null, -1),
              ce("新增模板 ")
            ]))
          ]),
          r.templates.length === 0 ? (E(), V("div", la, t[15] || (t[15] = [
            j("i", { class: "fa-solid fa-file-alt je-text-3xl je-mb-2" }, null, -1),
            j("p", { class: "je-text-sm" }, "暂无模板", -1)
          ]))) : (E(), V("div", oa, [
            (E(!0), V(Z, null, Ms(r.templates, (l) => (E(), V("div", {
              key: l.id,
              class: "je-template-item"
            }, [
              j("div", aa, [
                j("span", ua, oe(l.name), 1),
                j("span", fa, oe(i.getPreview(l.content)), 1)
              ]),
              j("div", ca, [
                j("button", {
                  onClick: (o) => i.insertTemplate(l),
                  class: "je-action-btn je-text-green-600",
                  title: "插入到编辑器"
                }, t[16] || (t[16] = [
                  j("i", { class: "fa-solid fa-arrow-right" }, null, -1)
                ]), 8, da),
                j("button", {
                  onClick: (o) => i.startEdit(l),
                  class: "je-action-btn je-text-blue-600",
                  title: "编辑"
                }, t[17] || (t[17] = [
                  j("i", { class: "fa-solid fa-edit" }, null, -1)
                ]), 8, ha),
                j("button", {
                  onClick: (o) => i.deleteTemplate(l),
                  class: "je-action-btn je-text-red-500",
                  title: "删除"
                }, t[18] || (t[18] = [
                  j("i", { class: "fa-solid fa-trash" }, null, -1)
                ]), 8, pa)
              ])
            ]))), 128))
          ]))
        ]))
      ])
    ])
  ])) : q("", !0);
}
const ma = /* @__PURE__ */ on(Ko, [["render", ga], ["__scopeId", "data-v-4abdc2e7"]]), ya = {
  name: "JsonEditor",
  components: {
    JsonEditorNode: Hs,
    TemplateDialog: ma
  },
  props: {
    modelValue: {
      type: [Object, Array, String, Number, Boolean],
      default: () => ({})
    },
    templateApiUrl: {
      type: String,
      default: "/api/templates"
    }
  },
  emits: ["update:modelValue", "change", "error"],
  data() {
    return {
      internalValue: null,
      rawMode: !1,
      rawJson: "",
      parseError: null,
      rawParseError: null,
      enabled: !0,
      filterText: "",
      collapseVersion: 0,
      globalCollapsed: null,
      showTemplateDialog: !1,
      internalTemplateApiUrl: ""
    };
  },
  computed: {
    currentTemplateApiUrl() {
      return this.internalTemplateApiUrl || this.templateApiUrl;
    },
    rootType() {
      return this.internalValue === null ? "null" : Array.isArray(this.internalValue) ? "Array" : typeof this.internalValue == "object" ? "Object" : typeof this.internalValue;
    },
    itemCount() {
      return Array.isArray(this.internalValue) ? `${this.internalValue.length} 个元素` : typeof this.internalValue == "object" && this.internalValue !== null ? `${Object.keys(this.internalValue).length} 个属性` : "";
    },
    filteredPaths() {
      if (!this.filterText) return [];
      const e = [], t = this.filterText.toLowerCase(), s = (n, r) => {
        n != null && (typeof n == "object" ? Array.isArray(n) ? n.forEach((i, l) => {
          const o = r ? `${r}.${l}` : `${l}`;
          String(l).includes(t) && e.push(o), s(i, o);
        }) : Object.keys(n).forEach((i) => {
          const l = r ? `${r}.${i}` : i;
          i.toLowerCase().includes(t) && e.push(l), s(n[i], l);
        }) : String(n).toLowerCase().includes(t) && e.push(r));
      };
      return s(this.internalValue, ""), e;
    }
  },
  watch: {
    modelValue: {
      handler(e) {
        this.internalValue = this.deepClone(e), this.rawJson = JSON.stringify(this.internalValue, null, 2);
      },
      immediate: !0,
      deep: !0
    }
  },
  methods: {
    // 深拷贝
    deepClone(e) {
      if (e === null || typeof e != "object") return e;
      if (Array.isArray(e)) return e.map((s) => this.deepClone(s));
      const t = {};
      for (const s in e)
        Object.prototype.hasOwnProperty.call(e, s) && (t[s] = this.deepClone(e[s]));
      return t;
    },
    // 更新值
    updateValue(e) {
      this.internalValue = e, this.rawJson = JSON.stringify(this.internalValue, null, 2), this.$emit("update:modelValue", this.deepClone(e)), this.$emit("change", this.deepClone(e));
    },
    // 切换源码模式
    toggleRawMode() {
      this.rawMode || (this.rawJson = JSON.stringify(this.internalValue, null, 2)), this.rawMode = !this.rawMode;
    },
    // 解析源码
    parseRawJson() {
      try {
        const e = JSON.parse(this.rawJson);
        this.internalValue = e, this.rawParseError = null, this.$emit("update:modelValue", this.deepClone(e)), this.$emit("change", this.deepClone(e));
      } catch (e) {
        this.rawParseError = `JSON 解析错误: ${e.message}`, this.$emit("error", e);
      }
    },
    // 格式化 JSON
    formatJson() {
      this.rawJson = JSON.stringify(this.internalValue, null, 2);
    },
    // 展开全部
    expandAll() {
      this.globalCollapsed = !1, this.collapseVersion++;
    },
    // 折叠全部
    collapseAll() {
      this.globalCollapsed = !0, this.collapseVersion++;
    },
    // ========== 外部 API ==========
    // 获取当前 JSON 值
    getValue() {
      return this.deepClone(this.internalValue);
    },
    // 设置 JSON 值
    setValue(e) {
      try {
        const t = typeof e == "string" ? JSON.parse(e) : e;
        return this.internalValue = this.deepClone(t), this.rawJson = JSON.stringify(this.internalValue, null, 2), this.parseError = null, this.$emit("update:modelValue", this.deepClone(this.internalValue)), this.$emit("change", this.deepClone(this.internalValue)), !0;
      } catch (t) {
        return this.parseError = `设置值失败: ${t.message}`, this.$emit("error", t), !1;
      }
    },
    // 获取 JSON 字符串
    getJsonString(e = 2) {
      return JSON.stringify(this.internalValue, null, e);
    },
    // 启用编辑器
    enable() {
      this.enabled = !0;
    },
    // 禁用编辑器
    disable() {
      this.enabled = !1;
    },
    // 检查是否启用
    isEnabled() {
      return this.enabled;
    },
    // 重置为初始值
    reset() {
      this.internalValue = this.deepClone(this.modelValue), this.rawJson = JSON.stringify(this.internalValue, null, 2), this.parseError = null, this.rawParseError = null;
    },
    // 清空编辑器
    clear() {
      this.internalValue = {}, this.rawJson = "{}", this.$emit("update:modelValue", {}), this.$emit("change", {});
    },
    // 验证 JSON
    validate() {
      try {
        return JSON.stringify(this.internalValue), { valid: !0, error: null };
      } catch (e) {
        return { valid: !1, error: e.message };
      }
    },
    // 在指定路径设置值
    setValueAtPath(e, t) {
      const s = e.split("."), n = this.deepClone(this.internalValue);
      let r = n;
      for (let i = 0; i < s.length - 1; i++) {
        const l = s[i];
        l in r || (r[l] = {}), r = r[l];
      }
      r[s[s.length - 1]] = t, this.updateValue(n);
    },
    // 获取指定路径的值
    getValueAtPath(e) {
      const t = e.split(".");
      let s = this.internalValue;
      for (const n of t) {
        if (s == null) return;
        s = s[n];
      }
      return this.deepClone(s);
    },
    // 删除指定路径的值
    deleteAtPath(e) {
      const t = e.split("."), s = this.deepClone(this.internalValue);
      let n = s;
      for (let i = 0; i < t.length - 1; i++) {
        const l = t[i];
        if (!(l in n)) return !1;
        n = n[l];
      }
      const r = t[t.length - 1];
      return Array.isArray(n) ? n.splice(parseInt(r), 1) : delete n[r], this.updateValue(s), !0;
    },
    // 收集匹配的路径
    collectMatchingPaths(e, t, s, n) {
      e != null && (typeof e == "object" ? Array.isArray(e) ? e.forEach((r, i) => {
        const l = t ? `${t}.${i}` : `${i}`;
        String(i).includes(n) && s.push(l), this.collectMatchingPaths(r, l, s, n);
      }) : Object.keys(e).forEach((r) => {
        const i = t ? `${t}.${r}` : r;
        r.toLowerCase().includes(n) && s.push(i), this.collectMatchingPaths(e[r], i, s, n);
      }) : String(e).toLowerCase().includes(n) && s.push(t));
    },
    // 设置筛选文本
    setFilter(e) {
      this.filterText = e;
    },
    // 清除筛选
    clearFilter() {
      this.filterText = "";
    },
    // 插入模板内容
    insertTemplate(e) {
      this.updateValue(e);
    },
    // 设置模板API地址
    setTemplateApiUrl(e) {
      this.internalTemplateApiUrl = e;
    }
  }
}, ba = {
  class: "je-editor je-bg-white je-rounded-lg je-shadow-lg je-border je-border-gray-200",
  ref: "editorContainer"
}, ja = { class: "je-toolbar je-flex je-items-center je-justify-between je-px-4 je-py-3 je-bg-gray-50 je-border-b je-border-gray-200 je-rounded-t-lg" }, xa = { class: "je-flex je-items-center je-gap-2" }, va = {
  key: 0,
  class: "je-text-xs je-px-2 je-py-0.5 je-bg-yellow-100 je-text-yellow-700 je-rounded"
}, _a = { class: "je-flex je-items-center je-gap-2" }, wa = { class: "je-relative" }, Ta = ["disabled"], Ca = ["disabled"], Sa = ["disabled"], Aa = ["disabled"], Ea = ["disabled"], Va = ["disabled"], Oa = {
  key: 0,
  class: "je-error je-text-red-500 je-p-4 je-bg-red-50 je-rounded"
}, Pa = {
  key: 0,
  class: "je-no-result je-text-gray-400 je-text-sm je-p-4 je-text-center"
}, Ra = ["disabled"], Ia = {
  key: 0,
  class: "je-mt-2 je-text-red-500 je-text-sm"
}, Ma = { class: "je-statusbar je-px-4 je-py-2 je-bg-gray-50 je-border-t je-border-gray-200 je-rounded-b-lg je-text-xs je-text-gray-500 je-flex je-justify-between" };
function Na(e, t, s, n, r, i) {
  const l = Is("JsonEditorNode"), o = Is("TemplateDialog");
  return E(), V("div", ba, [
    j("div", ja, [
      j("div", xa, [
        t[10] || (t[10] = j("span", { class: "je-font-semibold je-text-gray-700" }, "JSON Editor", -1)),
        r.enabled ? q("", !0) : (E(), V("span", va, "已禁用"))
      ]),
      j("div", _a, [
        j("div", wa, [
          t[12] || (t[12] = j("i", { class: "fa-solid fa-search je-absolute je-left-2 je-top-1/2 je--translate-y-1/2 je-text-gray-400 je-text-xs" }, null, -1)),
          Oe(j("input", {
            "onUpdate:modelValue": t[0] || (t[0] = (a) => r.filterText = a),
            type: "text",
            placeholder: "筛选...",
            class: "je-input je-pl-7 je-pr-2 je-py-1.5 je-text-xs je-border je-border-gray-300 je-rounded je-w-36",
            disabled: !r.enabled || r.rawMode
          }, null, 8, Ta), [
            [ts, r.filterText]
          ]),
          r.filterText ? (E(), V("button", {
            key: 0,
            onClick: t[1] || (t[1] = (a) => r.filterText = ""),
            class: "je-absolute je-right-1 je-top-1/2 je--translate-y-1/2 je-text-gray-400 hover:je-text-gray-600"
          }, t[11] || (t[11] = [
            j("i", { class: "fa-solid fa-times je-text-xs" }, null, -1)
          ]))) : q("", !0)
        ]),
        j("button", {
          onClick: t[2] || (t[2] = (...a) => i.expandAll && i.expandAll(...a)),
          class: "je-btn je-btn-blue je-px-3 je-py-1.5 je-text-xs je-rounded",
          disabled: !r.enabled
        }, t[13] || (t[13] = [
          j("i", { class: "fa-solid fa-expand je-mr-1" }, null, -1),
          ce("展开全部 ")
        ]), 8, Ca),
        j("button", {
          onClick: t[3] || (t[3] = (...a) => i.collapseAll && i.collapseAll(...a)),
          class: "je-btn je-btn-blue je-px-3 je-py-1.5 je-text-xs je-rounded",
          disabled: !r.enabled
        }, t[14] || (t[14] = [
          j("i", { class: "fa-solid fa-compress je-mr-1" }, null, -1),
          ce("折叠全部 ")
        ]), 8, Sa),
        j("button", {
          onClick: t[4] || (t[4] = (...a) => i.formatJson && i.formatJson(...a)),
          class: "je-btn je-btn-purple je-px-3 je-py-1.5 je-text-xs je-rounded",
          disabled: !r.enabled
        }, t[15] || (t[15] = [
          j("i", { class: "fa-solid fa-code je-mr-1" }, null, -1),
          ce("格式化 ")
        ]), 8, Aa),
        j("button", {
          onClick: t[5] || (t[5] = (a) => r.showTemplateDialog = !0),
          class: "je-btn je-btn-green je-px-3 je-py-1.5 je-text-xs je-rounded",
          disabled: !r.enabled
        }, t[16] || (t[16] = [
          j("i", { class: "fa-solid fa-file-alt je-mr-1" }, null, -1),
          ce("模板 ")
        ]), 8, Ea),
        j("button", {
          onClick: t[6] || (t[6] = (...a) => i.toggleRawMode && i.toggleRawMode(...a)),
          class: "je-btn je-btn-gray je-px-3 je-py-1.5 je-text-xs je-rounded",
          disabled: !r.enabled
        }, [
          t[17] || (t[17] = j("i", { class: "fa-solid fa-file-code je-mr-1" }, null, -1)),
          ce(oe(r.rawMode ? "可视化" : "源码"), 1)
        ], 8, Va)
      ])
    ]),
    j("div", {
      class: Ee(["je-content je-p-4 je-min-h-200 je-max-h-600 je-overflow-auto", { "je-disabled": !r.enabled }])
    }, [
      r.rawMode ? (E(), V(Z, { key: 1 }, [
        Oe(j("textarea", {
          "onUpdate:modelValue": t[7] || (t[7] = (a) => r.rawJson = a),
          onBlur: t[8] || (t[8] = (...a) => i.parseRawJson && i.parseRawJson(...a)),
          class: "je-textarea je-w-full je-h-80 je-p-3 je-font-mono je-text-sm je-border je-border-gray-300 je-rounded je-resize-none",
          placeholder: "输入 JSON...",
          disabled: !r.enabled
        }, null, 40, Ra), [
          [ts, r.rawJson]
        ]),
        r.rawParseError ? (E(), V("div", Ia, [
          t[20] || (t[20] = j("i", { class: "fa-solid fa-exclamation-triangle je-mr-1" }, null, -1)),
          ce(oe(r.rawParseError), 1)
        ])) : q("", !0)
      ], 64)) : (E(), V(Z, { key: 0 }, [
        r.parseError ? (E(), V("div", Oa, [
          t[18] || (t[18] = j("i", { class: "fa-solid fa-exclamation-triangle je-mr-2" }, null, -1)),
          ce(oe(r.parseError), 1)
        ])) : (E(), V(Z, { key: 1 }, [
          r.filterText && i.filteredPaths.length === 0 ? (E(), V("div", Pa, t[19] || (t[19] = [
            j("i", { class: "fa-solid fa-search je-mr-2" }, null, -1),
            ce("未找到匹配的内容 ")
          ]))) : q("", !0),
          Oe(Q(l, {
            modelValue: r.internalValue,
            "onUpdate:modelValue": i.updateValue,
            depth: 0,
            filterText: r.filterText,
            filteredPaths: i.filteredPaths,
            currentPath: "",
            globalCollapsed: r.globalCollapsed,
            collapseVersion: r.collapseVersion
          }, null, 8, ["modelValue", "onUpdate:modelValue", "filterText", "filteredPaths", "globalCollapsed", "collapseVersion"]), [
            [rt, !r.filterText || i.filteredPaths.length > 0]
          ])
        ], 64))
      ], 64))
    ], 2),
    j("div", Ma, [
      j("span", null, "类型: " + oe(i.rootType), 1),
      j("span", null, oe(i.itemCount), 1)
    ]),
    Q(o, {
      visible: r.showTemplateDialog,
      "onUpdate:visible": t[9] || (t[9] = (a) => r.showTemplateDialog = a),
      apiBaseUrl: i.currentTemplateApiUrl,
      onInsert: i.insertTemplate
    }, null, 8, ["visible", "apiBaseUrl", "onInsert"])
  ], 512);
}
const Ls = /* @__PURE__ */ on(ya, [["render", Na], ["__scopeId", "data-v-c6d65c75"]]);
function lt(e) {
  if (e === null || typeof e != "object") return e;
  if (Array.isArray(e)) return e.map((s) => lt(s));
  const t = {};
  for (const s in e)
    Object.prototype.hasOwnProperty.call(e, s) && (t[s] = lt(e[s]));
  return t;
}
class $r {
  constructor(t = {}) {
    this.options = t, this.container = null, this.app = null, this.vm = null, this.editorRef = null, this.isDestroyed = !1, this._value = t.initialValue || {};
  }
  /**
   * 挂载编辑器到DOM容器
   * @param {string|HTMLElement} container - DOM选择器或元素
   */
  mount(t) {
    if (this.isDestroyed)
      return console.warn("JsonEditor: Instance has been destroyed"), this;
    let s;
    if (typeof t == "string" ? s = document.querySelector(t) : s = t, !s)
      return console.error("JsonEditor: Container element not found"), this;
    if (s.tagName === "TEXTAREA") {
      this.sourceTextarea = s;
      const c = s.value.trim();
      if (c && !this.options.initialValue)
        try {
          this.options.initialValue = JSON.parse(c), this._value = this.options.initialValue;
        } catch {
          console.warn("JsonEditor: Failed to parse textarea content as JSON, using default value");
        }
      s.style.display = "none", this.container = document.createElement("div"), this.container.className = "json-editor-container", s.parentNode.insertBefore(this.container, s.nextSibling);
    } else
      this.container = s, this.sourceTextarea = null;
    const n = this, { initialValue: r = {}, onChange: i, onError: l, templateApiUrl: o } = this.options, a = o || this.container.dataset.templateapiurl || this.container.getAttribute("data-templateApiUrl") || "/api/templates";
    this._templateApiUrl = a;
    const d = {
      setup() {
        const c = Ei(lt(r)), p = Vi(null), T = (M) => {
          c.value = M, n._value = M, n.sourceTextarea && (n.sourceTextarea.value = JSON.stringify(M, null, 2)), typeof i == "function" && i(M);
        }, C = (M) => {
          typeof l == "function" && l(M);
        };
        return n._jsonValue = c, n._getEditorRef = () => p.value, () => Yl(Ls, {
          ref: p,
          modelValue: c.value,
          "onUpdate:modelValue": (M) => {
            c.value = M;
          },
          onChange: T,
          onError: C,
          templateApiUrl: n._templateApiUrl
        });
      }
    };
    return this.app = jo(d), this.vm = this.app.mount(this.container), setTimeout(() => {
      this.editorRef = this._getEditorRef();
    }, 0), this;
  }
  /**
   * 获取当前JSON值
   */
  getValue() {
    return this.editorRef ? this.editorRef.getValue() : lt(this._value);
  }
  /**
   * 设置JSON值
   * @param {Object|Array|string} value - JSON值或JSON字符串
   */
  setValue(t) {
    if (this.editorRef)
      return this.editorRef.setValue(t);
    try {
      return this._value = typeof t == "string" ? JSON.parse(t) : lt(t), this._jsonValue && (this._jsonValue.value = this._value), !0;
    } catch (s) {
      return console.error("JsonEditor: Failed to set value", s), !1;
    }
  }
  /**
   * 获取格式化的JSON字符串
   * @param {number} indent - 缩进空格数
   */
  getJsonString(t = 2) {
    return this.editorRef ? this.editorRef.getJsonString(t) : JSON.stringify(this._value, null, t);
  }
  /**
   * 启用编辑器
   */
  enable() {
    return this.editorRef && this.editorRef.enable(), this;
  }
  /**
   * 禁用编辑器
   */
  disable() {
    return this.editorRef && this.editorRef.disable(), this;
  }
  /**
   * 检查编辑器是否启用
   */
  isEnabled() {
    return this.editorRef ? this.editorRef.isEnabled() : !0;
  }
  /**
   * 重置为初始值
   */
  reset() {
    return this.editorRef && this.editorRef.reset(), this;
  }
  /**
   * 清空编辑器
   */
  clear() {
    return this.editorRef && this.editorRef.clear(), this;
  }
  /**
   * 验证JSON
   */
  validate() {
    if (this.editorRef)
      return this.editorRef.validate();
    try {
      return JSON.stringify(this._value), { valid: !0, error: null };
    } catch (t) {
      return { valid: !1, error: t.message };
    }
  }
  /**
   * 在指定路径设置值
   * @param {string} path - 路径，如 "user.name" 或 "items.0.id"
   * @param {*} value - 要设置的值
   */
  setValueAtPath(t, s) {
    return this.editorRef && this.editorRef.setValueAtPath(t, s), this;
  }
  /**
   * 获取指定路径的值
   * @param {string} path - 路径
   */
  getValueAtPath(t) {
    if (this.editorRef)
      return this.editorRef.getValueAtPath(t);
    const s = t.split(".");
    let n = this._value;
    for (const r of s) {
      if (n == null) return;
      n = n[r];
    }
    return lt(n);
  }
  /**
   * 删除指定路径的值
   * @param {string} path - 路径
   */
  deleteAtPath(t) {
    return this.editorRef ? this.editorRef.deleteAtPath(t) : !1;
  }
  /**
   * 展开所有节点
   */
  expandAll() {
    return this.editorRef && this.editorRef.expandAll(), this;
  }
  /**
   * 折叠所有节点
   */
  collapseAll() {
    return this.editorRef && this.editorRef.collapseAll(), this;
  }
  /**
   * 设置筛选文本
   * @param {string} text - 筛选关键词
   */
  setFilter(t) {
    return this.editorRef && this.editorRef.setFilter(t), this;
  }
  /**
   * 清除筛选
   */
  clearFilter() {
    return this.editorRef && this.editorRef.clearFilter(), this;
  }
  /**
   * 设置模板API地址
   * @param {string} url - API地址
   */
  setTemplateApiUrl(t) {
    return this._templateApiUrl = t, this.editorRef && this.editorRef.setTemplateApiUrl(t), this;
  }
  /**
   * 获取模板API地址
   */
  getTemplateApiUrl() {
    return this.editorRef ? this.editorRef.currentTemplateApiUrl : this._templateApiUrl;
  }
  /**
   * 销毁编辑器实例
   */
  destroy() {
    return this.app && (this.app.unmount(), this.app = null), this.vm = null, this.editorRef = null, this.isDestroyed = !0, this.sourceTextarea ? (this.sourceTextarea.style.display = "", this.container && this.container.parentNode && this.container.parentNode.removeChild(this.container), this.sourceTextarea = null) : this.container && (this.container.innerHTML = ""), this.container = null, this;
  }
  /**
   * 检查实例是否已销毁
   */
  isInstanceDestroyed() {
    return this.isDestroyed;
  }
}
function Da(e = {}) {
  const t = new $r(e);
  return e.container && t.mount(e.container), t;
}
const Ua = {
  create: Da,
  JsonEditor: Ls,
  JsonEditorNode: Hs,
  JsonEditorInstance: $r,
  // Vue插件安装方法
  install(e) {
    e.component("JsonEditor", Ls), e.component("JsonEditorNode", Hs);
  }
};
export {
  Ls as JsonEditor,
  $r as JsonEditorInstance,
  Hs as JsonEditorNode,
  Da as create,
  Ua as default
};
