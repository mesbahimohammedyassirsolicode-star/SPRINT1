let numbers=[30, 20, 40, 10];
let max =numbers[0];

for (let i = 0; i < numbers.length; i++) {
    console.log(`the number in the table is ${numbers[i]}`);
}

for (let i = 0; i < numbers.length; i++) {
    if(max<numbers[i]){
        max=numbers[i];
    }
    
}
console.log(`the maximum number is: ${max}`);
