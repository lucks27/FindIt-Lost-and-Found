const listeners = {};

export function on(event, cb) {
  if (!listeners[event]) listeners[event] = [];
  listeners[event].push(cb);
  return () => off(event, cb);
}

export function off(event, cb) {
  if (!listeners[event]) return;
  listeners[event] = listeners[event].filter((fn) => fn !== cb);
}

export function emit(event, payload) {
  const list = listeners[event] || [];
  list.forEach((fn) => {
    try {
      fn(payload);
    } catch (e) {
      console.warn('EventBus listener error for', event, e);
    }
  });
}

export default { on, off, emit };
