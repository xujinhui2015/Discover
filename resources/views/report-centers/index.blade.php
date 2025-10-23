<style>
    /* 现代化设计样式 */
    :root {
        --primary-color: #00adb5;
        --primary-light: #e0fbfc;
        --secondary-color: #253237;
        --text-primary: #212529;
        --text-secondary: #6c757d;
        --bg-light: #f8f9fa;
        --border-color: #e9ecef;
        --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --radius-sm: 0.25rem;
        --radius-md: 0.375rem;
        --radius-lg: 0.5rem;
        --transition: all 0.3s ease;
    }

    .report-container {
        padding: 20px 0;
    }

    .report-section {
        margin-bottom: 32px;
    }

    .report-section h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-light);
    }

    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .report-card {
        display: block;
        padding: 24px;
        background-color: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateY(0);
        opacity: 0;
        animation: fadeInUp 0.5s ease forwards;
    }

    /* 为不同的卡片添加延迟动画 */
    .report-card:nth-child(1) { animation-delay: 0.1s; }
    .report-card:nth-child(2) { animation-delay: 0.2s; }
    .report-card:nth-child(3) { animation-delay: 0.3s; }
    .report-card:nth-child(4) { animation-delay: 0.4s; }

    .report-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }

    .report-card:hover .icon-container {
        transform: scale(1.05) rotate(2deg);
        background-color: var(--primary-color);
        color: white;
    }

    .report-card .icon-container {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        border-radius: var(--radius-md);
        background-color: var(--primary-light);
        margin-bottom: 16px;
        color: var(--primary-color);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* 淡入上移动画 */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .report-card .icon-container i {
        font-size: 1.5rem;
    }

    .report-card h3 {
        font-size: 1rem;
        font-weight: 500;
        margin: 0;
        color: var(--text-primary);
        line-height: 1.4;
    }

    /* 响应式设计 */
    @media (max-width: 992px) {
        .report-grid {
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }
    }

    @media (max-width: 768px) {
        .report-container {
            padding: 15px 0;
        }
        
        .report-section {
            margin-bottom: 24px;
        }
        
        .report-section h2 {
            font-size: 1.1rem;
            margin-bottom: 12px;
            padding-bottom: 6px;
        }
        
        .report-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }
        
        .report-card {
            padding: 18px;
        }
        
        .report-card .icon-container {
            width: 50px;
            height: 50px;
            margin-bottom: 12px;
        }
        
        .report-card .icon-container i {
            font-size: 1.3rem;
        }
    }

    @media (max-width: 576px) {
        .report-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .report-card {
            padding: 16px;
            display: flex;
            align-items: center;
            animation: fadeInUp 0.4s ease forwards;
        }
        
        .report-card .icon-container {
            margin-bottom: 0;
            margin-right: 12px;
            width: 48px;
            height: 48px;
        }
        
        .report-card .icon-container i {
            font-size: 1.2rem;
        }
        
        .report-card h3 {
            font-size: 0.95rem;
            flex: 1;
        }
        
        /* 移动设备上的动画延迟调整 */
        .report-card:nth-child(1) { animation-delay: 0.05s; }
        .report-card:nth-child(2) { animation-delay: 0.1s; }
        .report-card:nth-child(3) { animation-delay: 0.15s; }
        .report-card:nth-child(4) { animation-delay: 0.2s; }
    }

    @media (max-width: 360px) {
        .report-card {
            padding: 14px;
        }
        
        .report-card h3 {
            font-size: 0.9rem;
        }
    }
</style>

<div class="report-container">
    <!-- 财务相关报表 -->
    <div class="report-section">
        <h2>财务相关</h2>
        <div class="report-grid">
            <a class="report-card" href="{{route('financial.settlement-history')}}">
                <div class="icon-container">
                    <i class="feather icon-credit-card"></i>
                </div>
                <h3>结算往来帐</h3>
            </a>
            <a class="report-card" href="{{route('financial.cost-order-statistical')}}">
                <div class="icon-container">
                    <i class="feather icon-pie-chart"></i>
                </div>
                <h3>费用汇总</h3>
            </a>
            <a class="report-card" href="{{route('financial.unsettled-cost')}}">
                <div class="icon-container">
                    <i class="feather icon-file-text"></i>
                </div>
                <h3>未结算费用报表</h3>
            </a>
        </div>
    </div>

    <!-- 采购相关报表 -->
    <div class="report-section">
        <h2>采购相关</h2>
        <div class="report-grid">
            <a class="report-card" href="{{route('purchase-report.items')}}">
                <div class="icon-container">
                    <i class="feather icon-shopping-cart"></i>
                </div>
                <h3>采购入库明细</h3>
            </a>
            <a class="report-card" href="{{route('purchase-report.summary-by-supplier')}}">
                <div class="icon-container">
                    <i class="feather icon-users"></i>
                </div>
                <h3>采购入库汇总(供应商)</h3>
            </a>
            <a class="report-card" href="{{ route('purchase-report.summary-by-sku') }}">
                <div class="icon-container">
                    <i class="feather icon-box"></i>
                </div>
                <h3>采购入库汇总(物料)</h3>
            </a>
            <a class="report-card" href="{{route('purchase-report.order-amount')}}">
                <div class="icon-container">
                    <i class="feather icon-dollar-sign"></i>
                </div>
                <h3>采购订单金额</h3>
            </a>
        </div>
    </div>

    <!-- 销售相关报表 -->
    <div class="report-section">
        <h2>销售相关</h2>
        <div class="report-grid">
            <a class="report-card" href="{{route('sale-report.items')}}">
                <div class="icon-container">
                    <i class="feather icon-bar-chart"></i>
                </div>
                <h3>销售成本毛利明细</h3>
            </a>
            <a class="report-card" href="{{route('sale-report.summary-by-customer')}}">
                <div class="icon-container">
                    <i class="feather icon-user"></i>
                </div>
                <h3>销售出库汇总(客户)</h3>
            </a>
            <a class="report-card" href="{{route('sale-report.summary-by-sku')}}">
                <div class="icon-container">
                    <i class="feather icon-tag"></i>
                </div>
                <h3>销售出库汇总(物料)</h3>
            </a>
            <a class="report-card" href="{{route('sale-report.order-amount')}}">
                <div class="icon-container">
                    <i class="feather icon-trending-up"></i>
                </div>
                <h3>销售订单金额</h3>
            </a>
        </div>
    </div>

    <!-- 生产相关报表 -->
    <div class="report-section">
        <h2>生产相关</h2>
        <div class="report-grid">
            <a class="report-card" href="{{route('make-product-report.apply-for-items')}}">
                <div class="icon-container">
                    <i class="feather icon-truck"></i>
                </div>
                <h3>领料出库明细</h3>
            </a>
            <a class="report-card" href="{{route('make-product-report.apply-for-summary')}}">
                <div class="icon-container">
                    <i class="feather icon-list"></i>
                </div>
                <h3>领料出库汇总</h3>
            </a>
            <a class="report-card" href="{{route('make-product-report.items')}}">
                <div class="icon-container">
                    <i class="feather icon-refresh-cw"></i>
                </div>
                <h3>生产入库明细</h3>
            </a>
            <a class="report-card" href="{{route('make-product-report.summary')}}">
                <div class="icon-container">
                    <i class="feather icon-check-square"></i>
                </div>
                <h3>生产入库汇总</h3>
            </a>
        </div>
    </div>
</div>


