const isDev = import.meta.env.DEV;

const noop = () => {};

const callConsole = (method, args) => {
  if (!isDev) return;
  const fn = globalThis.console?.[method];
  if (typeof fn === "function") {
    fn(...args);
  }
};

export const logger = {
  log: (...args) => callConsole("log", args),
  info: (...args) => callConsole("info", args),
  warn: (...args) => callConsole("warn", args),
  error: (...args) => callConsole("error", args),
  group: (...args) => callConsole("group", args),
  groupEnd: (...args) => callConsole("groupEnd", args),
  time: (...args) => callConsole("time", args),
  timeEnd: (...args) => callConsole("timeEnd", args),
  noop,
};

export default logger;
