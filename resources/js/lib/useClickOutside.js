import { useEffect } from 'react';

/**
 * Calls `handler` when a click happens outside the referenced element.
 * Replaces Alpine's `@click.outside` directive.
 */
export default function useClickOutside(ref, handler, enabled = true) {
  useEffect(() => {
    if (!enabled) {
      return undefined;
    }

    function onPointerDown(event) {
      if (ref.current && !ref.current.contains(event.target)) {
        handler(event);
      }
    }

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('touchstart', onPointerDown);

    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('touchstart', onPointerDown);
    };
  }, [ref, handler, enabled]);
}
