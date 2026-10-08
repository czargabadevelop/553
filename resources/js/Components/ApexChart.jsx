import { useEffect, useRef } from 'react';
import ApexCharts from 'apexcharts';

export default function ApexChart({ options, className, id }) {
  const containerRef = useRef(null);
  const chartRef = useRef(null);

  useEffect(() => {
    if (!containerRef.current) {
      return undefined;
    }

    chartRef.current = new ApexCharts(containerRef.current, options);
    chartRef.current.render();

    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
    // Options are static configs; serialising them keeps the effect honest.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [JSON.stringify(options)]);

  return <div id={id} ref={containerRef} className={className} />;
}
