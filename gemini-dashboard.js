document.addEventListener('DOMContentLoaded', () => {
    // Set Current Date
    const dateEl = document.getElementById('current-date');
    dateEl.innerText = new Date().toLocaleDateString('en-US', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
    });

    // Mock Data (In a real app, this comes from a PHP/API call)
    const transactions = [
        { date: '2023-10-25', desc: 'Apple Store', cat: 'Electronics', amt: -999.00 },
        { date: '2023-10-24', desc: 'Payroll Deposit', cat: 'Income', amt: 4500.00 },
        { date: '2023-10-23', desc: 'Starbucks', cat: 'Food', amt: -12.50 },
    ];

    const tableBody = document.getElementById('transaction-body');

    transactions.forEach(tx => {
        const row = document.createElement('tr');
        const amtClass = tx.amt > 0 ? 'amount-green' : 'amount-red';
        
        row.innerHTML = `
            <td>${tx.date}</td>
            <td>${tx.desc}</td>
            <td>${tx.cat}</td>
            <td class="${amtClass}">${tx.amt > 0 ? '+' : ''}$${Math.abs(tx.amt).toFixed(2)}</td>
        `;
        tableBody.appendChild(row);
    });
});