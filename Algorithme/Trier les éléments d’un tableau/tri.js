let array_1=[1,4,2,5,2,8];
let array_2=[7,4,5,5,1,10];

function tri(array){
    for(i=0;i<array.length;i++){

        for(j=i+1;j<array.length;j++){
            if(array[j]<array[i]){
               let temp=array[i];
                array[i]=array[j];
                array[j]=temp;
            }
            
        }
    }
    return array;
}

console.log(tri(array_2));